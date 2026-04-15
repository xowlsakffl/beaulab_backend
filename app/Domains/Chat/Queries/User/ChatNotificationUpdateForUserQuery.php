<?php

namespace App\Domains\Chat\Queries\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Models\Chat;
use Illuminate\Support\Facades\DB;

/**
 * 채팅 participant 단위 알림 설정 저장을 담당한다.
 * 같은 채팅방의 참여자만 자신의 notifications_enabled 값을 바꿀 수 있다.
 */
final class ChatNotificationUpdateForUserQuery
{
    public function update(Chat $chat, AccountUser $user, bool $notificationsEnabled): Chat
    {
        return DB::transaction(function () use ($chat, $user, $notificationsEnabled): Chat {
            $participant = $chat->participants()
                ->where('account_user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($participant === null) {
                throw new CustomException(ErrorCode::FORBIDDEN, '채팅방 참여자만 알림 설정을 변경할 수 있습니다.');
            }

            $participant->forceFill([
                'notifications_enabled' => $notificationsEnabled,
            ])->save();

            return $chat->fresh([
                'lastMessage.sender:id,nickname,email',
                'lastMessage.attachments',
                'participants.accountUser:id,nickname,email',
            ]);
        });
    }
}
