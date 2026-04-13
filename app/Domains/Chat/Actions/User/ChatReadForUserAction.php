<?php

namespace App\Domains\Chat\Actions\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Dto\User\ChatForUserDto;
use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Queries\User\ChatReadForUserQuery;

final class ChatReadForUserAction
{
    public function __construct(
        private readonly ChatReadForUserQuery $query,
    ) {}

    public function execute(Chat $chat, AccountUser $user, array $payload): array
    {
        $chat = $this->query->read($chat, $user, $payload);

        return [
            'chat' => ChatForUserDto::fromModel($chat, (int) $user->id),
        ];
    }
}
