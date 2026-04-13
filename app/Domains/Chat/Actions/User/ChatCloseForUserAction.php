<?php

namespace App\Domains\Chat\Actions\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Dto\User\ChatForUserDto;
use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Queries\User\ChatCloseForUserQuery;

final class ChatCloseForUserAction
{
    public function __construct(
        private readonly ChatCloseForUserQuery $query,
    ) {}

    public function execute(Chat $chat, AccountUser $user): array
    {
        $chat = $this->query->close($chat, $user);

        return [
            'chat' => ChatForUserDto::fromModel($chat, (int) $user->id),
        ];
    }
}
