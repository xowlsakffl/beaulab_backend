<?php

namespace App\Domains\Chat\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Chat\Dto\User\ChatForUserDto;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Queries\User\ChatOpenOrCreateForUserQuery;
use App\Domains\Chat\Support\ChatMatchKey;

final class ChatOpenOrCreateForUserAction
{
    public function __construct(
        private readonly ChatOpenOrCreateForUserQuery $query,
    ) {}

    public function execute(AccountUser $user, int $peerUserId): array
    {
        if ((int) $user->id === $peerUserId) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '본인과는 채팅방을 만들 수 없습니다.');
        }

        $peer = $this->query->findActivePeer($peerUserId);
        $matchKey = ChatMatchKey::forUsers((int) $user->id, $peerUserId);

        $chat = $this->query->openOrCreate($user, $peer, $matchKey);

        return [
            'chat' => ChatForUserDto::fromModel($chat, (int) $user->id),
        ];
    }
}
