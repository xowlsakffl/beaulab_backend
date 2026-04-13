<?php

namespace App\Domains\Chat\Queries\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Chat\Models\ChatParticipant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 메시지 저장 트랜잭션과 발송 가능성 검증을 담당한다.
 * client_message_id가 있으면 앱 재시도에 대해 멱등성을 보장한다.
 */
final class ChatMessageSendForUserQuery
{
    public function store(Chat $chat, AccountUser $user, array $payload): array
    {
        return DB::transaction(function () use ($chat, $user, $payload): array {
            $lockedChat = Chat::query()
                ->whereKey($chat->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertSendable($lockedChat, (int) $user->id);

            $clientMessageId = $this->normalizeNullableString($payload['client_message_id'] ?? null);
            if ($clientMessageId !== null) {
                // 모바일 네트워크 재시도로 같은 메시지가 다시 들어와도 중복 저장하지 않는다.
                $existingMessage = ChatMessage::query()
                    ->where('chat_id', $lockedChat->id)
                    ->where('sender_user_id', $user->id)
                    ->where('client_message_id', $clientMessageId)
                    ->with('sender:id,name,email')
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
                'message' => $message->load('sender:id,name,email'),
                'created' => true,
            ];
        });
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
}
