<?php

namespace App\Domains\Chat\Actions\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Dto\User\ChatForUserDto;
use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Queries\User\ChatReadForUserQuery;

/**
 * 채팅방 읽음 처리 유스케이스.
 * 참여자 검증과 last_read 갱신은 Query에 위임하고 응답 DTO만 구성한다.
 */
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
