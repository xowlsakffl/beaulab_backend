<?php

namespace App\Domains\Chat\Queries\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Chat\Models\ChatParticipant;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 메시지 저장 트랜잭션과 발송 가능성 검증을 담당한다.
 * client_message_id가 있으면 앱 재시도에 대해 멱등성을 보장한다.
 */
final class ChatMessageSendForUserQuery
{
    public function __construct(
        private readonly MediaAttachDeleteAction $mediaAttachDeleteAction,
    ) {}

    public function store(Chat $chat, AccountUser $user, array $payload): array
    {
        return DB::transaction(function () use ($chat, $user, $payload): array {
            $attachments = $this->attachments($payload['attachments'] ?? []);

            $lockedChat = Chat::query()
                ->whereKey($chat->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertSendable($lockedChat, (int) $user->id);

            return $this->storeOnLockedChat($lockedChat, $user, $payload, $attachments);
        });
    }

    public function storeFirst(AccountUser $user, AccountUser $peer, string $matchKey, array $payload): array
    {
        try {
            return $this->storeFirstInTransaction($user, $peer, $matchKey, $payload);
        } catch (QueryException $exception) {
            if (! $this->isDuplicateKeyException($exception)) {
                throw $exception;
            }

            return $this->storeFirstInTransaction($user, $peer, $matchKey, $payload);
        }
    }

    public function findActivePeer(int $peerUserId): AccountUser
    {
        $peer = AccountUser::query()->find($peerUserId);

        if (! $peer instanceof AccountUser) {
            throw new CustomException(ErrorCode::USER_NOT_FOUND);
        }

        if (! $peer->isActive()) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '활성 상태의 사용자와만 채팅할 수 있습니다.');
        }

        return $peer;
    }

    private function storeFirstInTransaction(AccountUser $user, AccountUser $peer, string $matchKey, array $payload): array
    {
        return DB::transaction(function () use ($user, $peer, $matchKey, $payload): array {
            $attachments = $this->attachments($payload['attachments'] ?? []);
            $lockedChat = $this->openOrCreateChat($user, $peer, $matchKey);

            $this->assertSendable($lockedChat, (int) $user->id);

            return $this->storeOnLockedChat($lockedChat, $user, $payload, $attachments);
        });
    }

    /**
     * @param  list<UploadedFile>  $attachments
     * @return array{message: ChatMessage, created: bool}
     */
    private function storeOnLockedChat(Chat $lockedChat, AccountUser $user, array $payload, array $attachments): array
    {
        $clientMessageId = $this->normalizeNullableString($payload['client_message_id'] ?? null);
        if ($clientMessageId !== null) {
            // 모바일 네트워크 재시도로 같은 메시지가 다시 들어와도 중복 저장하지 않는다.
            $existingMessage = ChatMessage::query()
                ->where('chat_id', $lockedChat->id)
                ->where('sender_user_id', $user->id)
                ->where('client_message_id', $clientMessageId)
                ->with(['sender:id,name,email', 'attachments'])
                ->first();

            if ($existingMessage instanceof ChatMessage) {
                return [
                    'message' => $existingMessage,
                    'created' => false,
                ];
            }
        }

        $replyToMessageId = (int) ($payload['reply_to_message_id'] ?? 0);
        if ($replyToMessageId > 0) {
            $replyExists = ChatMessage::query()
                ->where('chat_id', $lockedChat->id)
                ->whereKey($replyToMessageId)
                ->exists();

            if (! $replyExists) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '답장 대상 메시지를 찾을 수 없습니다.');
            }
        }

        $message = ChatMessage::create([
            'chat_id' => $lockedChat->id,
            'sender_user_id' => $user->id,
            'client_message_id' => $clientMessageId,
            'message_type' => $payload['message_type'] ?? ChatMessage::TYPE_TEXT,
            'body' => $this->normalizeNullableString($payload['body'] ?? null),
            'reply_to_message_id' => $replyToMessageId > 0 ? $replyToMessageId : null,
            'metadata' => $payload['metadata'] ?? null,
        ]);

        $this->storeAttachments($message, $attachments);

        $lockedChat->forceFill([
            'last_message_id' => $message->id,
            'last_message_at' => $message->created_at,
        ])->save();

        $lockedChat->participants()
            ->where('account_user_id', $user->id)
            ->update([
                'last_read_message_id' => $message->id,
                'last_read_at' => now(),
            ]);

        return [
            'message' => $message->load(['sender:id,name,email', 'attachments']),
            'created' => true,
        ];
    }

    /**
     * @return Collection<int, int>
     */
    public function notificationRecipientIds(ChatMessage $message, AccountUser $sender): Collection
    {
        return ChatParticipant::query()
            ->where('chat_id', $message->chat_id)
            ->where('account_user_id', '!=', $sender->id)
            ->where('notifications_enabled', true)
            ->pluck('account_user_id');
    }

    private function openOrCreateChat(AccountUser $user, AccountUser $peer, string $matchKey): Chat
    {
        // 첫 메시지를 보낼 때만 채팅방 row를 만든다. 기존 빈 row가 있어도 여기서 메시지를 붙이면 목록에 노출된다.
        $chat = Chat::withTrashed()
            ->where('match_key', $matchKey)
            ->lockForUpdate()
            ->first();

        if (! $chat instanceof Chat) {
            $chat = Chat::create([
                'status' => Chat::STATUS_ACTIVE,
                'match_key' => $matchKey,
                'created_by_user_id' => $user->id,
            ]);

            $chat->participants()->createMany([
                ['account_user_id' => $user->id],
                ['account_user_id' => $peer->id],
            ]);

            return $chat;
        }

        if ($chat->trashed()) {
            $chat->restore();
        }

        if ($chat->status === Chat::STATUS_SUSPENDED) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '정지된 채팅방입니다.');
        }

        if ($chat->status !== Chat::STATUS_ACTIVE) {
            $chat->forceFill([
                'status' => Chat::STATUS_ACTIVE,
                'closed_at' => null,
            ])->save();
        }

        $chat->participants()->firstOrCreate(['account_user_id' => $user->id]);
        $chat->participants()->firstOrCreate(['account_user_id' => $peer->id]);

        return $chat;
    }

    private function assertSendable(Chat $chat, int $userId): void
    {
        if ($chat->status !== Chat::STATUS_ACTIVE) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '활성 채팅방에만 메시지를 보낼 수 있습니다.');
        }

        $isParticipant = $chat->participants()
            ->where('account_user_id', $userId)
            ->exists();

        if (! $isParticipant) {
            throw new CustomException(ErrorCode::FORBIDDEN, '채팅방 참여자만 메시지를 보낼 수 있습니다.');
        }
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function isDuplicateKeyException(QueryException $exception): bool
    {
        return ($exception->errorInfo[0] ?? null) === '23000'
            && (int) ($exception->errorInfo[1] ?? 0) === 1062;
    }

    /**
     * @return list<UploadedFile>
     */
    private function attachments(mixed $value): array
    {
        if ($value instanceof UploadedFile) {
            return [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn (mixed $file): bool => $file instanceof UploadedFile
        ));
    }

    /**
     * @param  list<UploadedFile>  $attachments
     */
    private function storeAttachments(ChatMessage $message, array $attachments): void
    {
        if ($attachments === []) {
            return;
        }

        $this->mediaAttachDeleteAction->attachMany(
            $message,
            $attachments,
            ChatMessage::MEDIA_COLLECTION_ATTACHMENTS,
            'chat/messages',
            'attachments',
            true,
        );
    }
}
