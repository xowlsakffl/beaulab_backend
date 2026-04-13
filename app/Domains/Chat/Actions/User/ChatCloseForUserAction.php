<?php

namespace App\Domains\Chat\Actions\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Dto\User\ChatForUserDto;
use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Queries\User\ChatCloseForUserQuery;

/**
 * 채팅 종료 유스케이스.
 * 한 참여자가 종료하면 채팅방 전체 상태가 CLOSED가 되는 정책을 Query 호출로 실행한다.
 */
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
