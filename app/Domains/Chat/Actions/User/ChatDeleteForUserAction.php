<?php

namespace App\Domains\Chat\Actions\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Dto\User\ChatForUserDto;
use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Queries\User\ChatDeleteForUserQuery;

/**
 * 사용자별 채팅 삭제 유스케이스.
 * 한 참여자가 삭제해도 채팅방 자체는 유지하고, 해당 사용자에게만 이전 메시지를 숨긴다.
 */
final class ChatDeleteForUserAction
{
    public function __construct(
        private readonly ChatDeleteForUserQuery $query,
    ) {}

    public function execute(Chat $chat, AccountUser $user): array
    {
        $chat = $this->query->deleteForUser($chat, $user);

        return [
            'chat' => ChatForUserDto::fromModel($chat, (int) $user->id),
        ];
    }
}
