<?php

namespace App\Domains\Chat\Queries\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Models\Chat;
use Illuminate\Support\Facades\DB;

final class ChatCloseForUserQuery
{
    public function close(Chat $chat, AccountUser $user): Chat
    {
        return DB::transaction(function () use ($chat, $user): Chat {
            $lockedChat = Chat::query()
                ->whereKey($chat->id)
                ->lockForUpdate()
                ->firstOrFail();

            $isParticipant = $lockedChat->participants()
                ->where('account_user_id', $user->id)
                ->exists();

            if (! $isParticipant) {
                throw new CustomException(ErrorCode::FORBIDDEN, '채팅방 참여자만 채팅을 종료할 수 있습니다.');
            }

            if ($lockedChat->status === Chat::STATUS_SUSPENDED) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '정지된 채팅방은 종료할 수 없습니다.');
            }

            if ($lockedChat->status !== Chat::STATUS_CLOSED) {
                $lockedChat->forceFill([
                    'status' => Chat::STATUS_CLOSED,
                    'closed_at' => now(),
                ])->save();
            }

            return $lockedChat->fresh([
                'lastMessage.sender:id,name,email',
                'participants.accountUser:id,name,email',
            ]);
        });
    }
}
