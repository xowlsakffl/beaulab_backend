<?php

namespace App\Domains\Chat\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Chat\Dto\User\ChatForUserDto;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Queries\User\ChatOpenOrCreateForUserQuery;
use App\Domains\Chat\Support\ChatMatchKey;

/**
 * 앱 사용자 1:1 채팅방 열기 유스케이스.
 * 자기 자신과의 채팅 금지, 상대 활성 상태 검증, match_key 생성을 담당하고 DB 처리는 Query에 위임한다.
 */
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
