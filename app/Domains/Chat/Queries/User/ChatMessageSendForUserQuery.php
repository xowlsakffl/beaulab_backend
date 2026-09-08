<?php

namespace App\Domains\Chat\Queries\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Models\AccountUserBlock;
use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Chat\Models\ChatParticipant;
use App\Domains\Chat\Support\ChatMatchKey;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 메시지 저장 트랜잭션
 * client_message_id가 있으면 앱 재시도에 대해 멱등성을 보장한다.
 * 채팅방 조회/생성, 발송 가능 여부 검증, 메시지 저장 트랜잭션은 같은 DB 접근을 한 흐름으로 처리한다.
 */
final class ChatMessageSendForUserQuery
{
    /**
     * 메시지 전송 진입점.
     * 기존 채팅방 ID가 들어오면 그 방에 바로 전송하고,
     * 없으면 peer_user_id 기준으로 1:1 채팅방을 찾거나 새로 만든 뒤 전송한다.
     *
     * @return array{message: ChatMessage, created: bool}
     */
    public function create(AccountUser $user, array $payload, ?Chat $chat = null): array
    {
        if ($chat instanceof Chat) {
            return DB::transaction(function () use ($chat, $user, $payload): array {
                $lockedChat = Chat::query()
                    ->whereKey($chat->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                return $this->createOnResolvedChat($lockedChat, $user, $payload);
            });
        }

        // 첫 메시지 전송은 방이 없을 수 있으므로 상대 사용자 정보와 1:1 식별키를 먼저 확정한다.
        $peerUserId = $this->requestedPeerUserId($user, $payload);
        $peer = $this->findActivePeer($peerUserId);
        $matchKey = ChatMatchKey::forUsers((int) $user->id, $peerUserId);

        try {
            return DB::transaction(function () use ($user, $payload, $peer, $matchKey): array {
                $lockedChat = $this->openOrCreateChat($user, $peer, $matchKey);

                return $this->createOnResolvedChat($lockedChat, $user, $payload);
            });
        } catch (QueryException $exception) {
            if (! $this->isDuplicateKeyException($exception)) {
                throw $exception;
            }

            // 첫 메시지 전송이 동시에 들어오면 match_key 유니크 충돌이 날 수 있으므로, 이미 만들어진 방을 다시 열어 재시도한다.
            return DB::transaction(function () use ($user, $payload, $peer, $matchKey): array {
                $lockedChat = $this->openOrCreateChat($user, $peer, $matchKey);

                return $this->createOnResolvedChat($lockedChat, $user, $payload);
            });
        }
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

    private function createOnResolvedChat(Chat $lockedChat, AccountUser $user, array $payload): array
    {
        // 방 상태와 참여 여부를 먼저 확인하고, 그 다음 상대와의 차단 관계를 검사한다.
        $this->assertSendable($lockedChat, (int) $user->id);
        $this->assertCanSendMessage(
            (int) $user->id,
            $this->peerUserId($lockedChat, (int) $user->id),
        );

        return $this->createOnLockedChat($lockedChat, $user, $payload);
    }

    private function requestedPeerUserId(AccountUser $user, array $payload): int
    {
        $peerUserId = (int) ($payload['peer_user_id'] ?? 0);

        if ($peerUserId <= 0) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '상대 사용자를 확인해 주세요.');
        }

        if ((int) $user->id === $peerUserId) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '본인과는 채팅방을 만들 수 없습니다.');
        }

        return $peerUserId;
    }

    private function findActivePeer(int $peerUserId): AccountUser
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

    /**
     * 1:1 채팅방을 찾고 없으면 만든다.
     * 삭제된 방이면 복구하고, 닫힌 방이면 다시 ACTIVE로 열어 기존 대화를 이어간다.
     */
    private function openOrCreateChat(AccountUser $user, AccountUser $peer, string $matchKey): Chat
    {
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

        // 복구되거나 다시 열린 방은 레거시 데이터 기준으로 한쪽 participant 행이 비어 있을 수 있어 보정한다.
        $chat->participants()->firstOrCreate(['account_user_id' => $user->id]);
        $chat->participants()->firstOrCreate(['account_user_id' => $peer->id]);

        return $chat;
    }

    private function peerUserId(Chat $chat, int $userId): int
    {
        $peerUserId = $chat->participants()
            ->where('account_user_id', '!=', $userId)
            ->value('account_user_id');

        if ($peerUserId === null) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '상대 사용자를 찾을 수 없습니다.');
        }

        return (int) $peerUserId;
    }

    // 모바일 네트워크 재시도로 같은 메시지가 다시 들어와도 중복 저장하지 않는다.
    /**
     * @return array{message: ChatMessage, created: bool}
     */
    private function createOnLockedChat(Chat $lockedChat, AccountUser $user, array $payload): array
    {
        $clientMessageId = $this->normalizeNullableString($payload['client_message_id'] ?? null);

        if ($clientMessageId !== null) {
            $existingMessage = ChatMessage::query()
                ->where('chat_id', $lockedChat->id)
                ->where('sender_user_id', $user->id)
                ->where('client_message_id', $clientMessageId)
                ->with(['sender:id,nickname,email', 'attachments'])
                ->first();

            if ($existingMessage instanceof ChatMessage) {
                return [
                    // 모바일 재시도 요청도 항상 원본 메시지를 돌려줘야 멱등하게 처리된다.
                    'message' => $existingMessage,
                    'created' => false,
                ];
            }
        }

        $replyToMessageId = (int) ($payload['reply_to_message_id'] ?? 0);

        if ($replyToMessageId > 0) {
            // 답장 대상은 같은 채팅방 안의 메시지여야만 한다.
            $replyExists = ChatMessage::query()
                ->where('chat_id', $lockedChat->id)
                ->whereKey($replyToMessageId)
                ->exists();

            if (! $replyExists) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '답장 대상 메시지를 찾을 수 없습니다.');
            }
        }

        $message = ChatMessage::create([
            'broadcast_pending' => true,
            'chat_id' => $lockedChat->id,
            'sender_user_id' => $user->id,
            'client_message_id' => $clientMessageId,
            'message_type' => $payload['message_type'] ?? ChatMessage::TYPE_TEXT,
            'body' => $this->normalizeNullableString($payload['body'] ?? null),
            'reply_to_message_id' => $replyToMessageId > 0 ? $replyToMessageId : null,
            'metadata' => $payload['metadata'] ?? null,
        ]);

        $lockedChat->forceFill([
            'last_message_id' => $message->id,
            'last_message_at' => $message->created_at,
        ])->save();

        // 보낸 사람은 방금 보낸 메시지를 이미 읽은 상태로 본다.
        $lockedChat->participants()
            ->where('account_user_id', $user->id)
            ->update([
                'last_read_message_id' => $message->id,
                'last_read_at' => now(),
            ]);

        return [
            'message' => $message->load(['sender:id,nickname,email', 'attachments']),
            'created' => true,
        ];
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

    private function assertCanSendMessage(int $senderUserId, int $peerUserId): void
    {
        // 양방향 차단을 한 번에 조회한 뒤, 누가 blocker인지로 에러 메시지를 분기한다.
        $blockerIds = AccountUserBlock::query()
            ->where(function ($query) use ($senderUserId, $peerUserId): void {
                $query
                    ->where('blocker_user_id', $senderUserId)
                    ->where('blocked_user_id', $peerUserId);
            })
            ->orWhere(function ($query) use ($senderUserId, $peerUserId): void {
                $query
                    ->where('blocker_user_id', $peerUserId)
                    ->where('blocked_user_id', $senderUserId);
            })
            ->pluck('blocker_user_id')
            ->map(static fn (mixed $blockerId): int => (int) $blockerId);

        if ($blockerIds->contains($senderUserId)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '차단 해제 후 메시지를 보낼 수 있습니다.');
        }

        if ($blockerIds->contains($peerUserId)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '메시지를 보낼 수 없습니다.');
        }
    }

    private function isDuplicateKeyException(QueryException $exception): bool
    {
        return ($exception->errorInfo[0] ?? null) === '23000'
            && (int) ($exception->errorInfo[1] ?? 0) === 1062;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
