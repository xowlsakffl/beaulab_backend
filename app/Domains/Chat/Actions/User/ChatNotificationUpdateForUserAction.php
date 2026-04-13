<?php

namespace App\Domains\Chat\Actions\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Dto\User\ChatForUserDto;
use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Queries\User\ChatNotificationUpdateForUserQuery;

/**
 * 채팅방별 알림 on/off 변경 유스케이스.
 * 전체 알림 설정이 아니라 특정 채팅 participant의 수신 여부만 변경한다.
 */
final class ChatNotificationUpdateForUserAction
{
    public function __construct(
        private readonly ChatNotificationUpdateForUserQuery $query,
    ) {}

    public function execute(Chat $chat, AccountUser $user, bool $notificationsEnabled): array
    {
        $chat = $this->query->update($chat, $user, $notificationsEnabled);

        return [
            'chat' => ChatForUserDto::fromModel($chat, (int) $user->id),
        ];
    }
}
