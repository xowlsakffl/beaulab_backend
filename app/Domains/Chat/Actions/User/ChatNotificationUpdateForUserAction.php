<?php

namespace App\Domains\Chat\Actions\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Dto\User\ChatForUserDto;
use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Queries\User\ChatNotificationUpdateForUserQuery;

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
