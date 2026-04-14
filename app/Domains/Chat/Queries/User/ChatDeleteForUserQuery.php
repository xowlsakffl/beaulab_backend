<?php

namespace App\Domains\Chat\Queries\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Models\Chat;
use Illuminate\Support\Facades\DB;

/**
 * 사용자별 채팅 삭제를 담당한다.
 * 채팅방 자체는 닫지 않고, 현재 참여자에게만 마지막 메시지까지 삭제된 것으로 기록한다.
 */
final class ChatDeleteForUserQuery
{
    public function deleteForUser(Chat $chat, AccountUser $user): Chat
    {
        return DB::transaction(function () use ($chat, $user): Chat {
            $lockedChat = Chat::query()
                ->whereKey($chat->id)
                ->lockForUpdate()
                ->firstOrFail();

            $participant = $lockedChat->participants()
                ->where('account_user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($participant === null) {
                throw new CustomException(ErrorCode::FORBIDDEN, '채팅방 참여자만 채팅을 삭제할 수 있습니다.');
            }

            $lastMessageId = $lockedChat->last_message_id ? (int) $lockedChat->last_message_id : null;

            $participant->forceFill([
                'deleted_until_message_id' => $lastMessageId,
                'deleted_at' => now(),
                'last_read_message_id' => $lastMessageId ?? $participant->last_read_message_id,
                'last_read_at' => $lastMessageId !== null ? now() : $participant->last_read_at,
            ])->save();

            return $lockedChat->fresh([
                'lastMessage.sender:id,name,email',
                'lastMessage.attachments',
                'participants.accountUser:id,name,email',
            ]);
        });
    }
}
