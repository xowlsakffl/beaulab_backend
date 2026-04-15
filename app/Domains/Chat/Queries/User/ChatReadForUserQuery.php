<?php

namespace App\Domains\Chat\Queries\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Models\ChatMessage;
use Illuminate\Support\Facades\DB;

/**
 * 채팅 읽음 상태 저장을 담당한다.
 * 명시 메시지 ID가 없으면 현재 채팅방의 마지막 메시지까지 읽은 것으로 처리한다.
 */
final class ChatReadForUserQuery
{
    public function read(Chat $chat, AccountUser $user, array $payload): Chat
    {
        return DB::transaction(function () use ($chat, $user, $payload): Chat {
            $participant = $chat->participants()
                ->where('account_user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($participant === null) {
                throw new CustomException(ErrorCode::FORBIDDEN, '채팅방 참여자만 읽음 처리할 수 있습니다.');
            }

            $messageId = (int) ($payload['last_read_message_id'] ?? 0);
            if ($messageId <= 0) {
                $messageId = (int) ChatMessage::query()
                    ->where('chat_id', $chat->id)
                    ->max('id');
            }

            if ($messageId > 0) {
                $messageExists = ChatMessage::query()
                    ->where('chat_id', $chat->id)
                    ->whereKey($messageId)
                    ->exists();

                if (! $messageExists) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, '같은 채팅방의 메시지만 읽음 처리할 수 있습니다.');
                }
            }

            $currentMessageId = (int) ($participant->last_read_message_id ?? 0);
            $nextMessageId = max($currentMessageId, $messageId);

            $participant->forceFill([
                'last_read_message_id' => $nextMessageId > 0 ? $nextMessageId : null,
                'last_read_at' => now(),
            ])->save();

            return $chat->fresh([
                'lastMessage.sender:id,nickname,email',
                'lastMessage.attachments',
                'participants.accountUser:id,nickname,email',
            ]);
        });
    }
}
