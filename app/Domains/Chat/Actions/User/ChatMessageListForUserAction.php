<?php

namespace App\Domains\Chat\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Dto\User\ChatMessageForUserDto;
use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Queries\User\ChatMessageListForUserQuery;

final class ChatMessageListForUserAction
{
    public function __construct(
        private readonly ChatMessageListForUserQuery $query,
    ) {}

    public function execute(Chat $chat, AccountUser $user, array $filters): array
    {
        $this->assertParticipant($chat, (int) $user->id);

        $result = $this->query->get($chat, $filters);

        return [
            'items' => $result['items']
                ->map(fn ($message) => ChatMessageForUserDto::fromModel($message, (int) $user->id))
                ->values()
                ->all(),
            'meta' => $result['meta'],
        ];
    }

    private function assertParticipant(Chat $chat, int $userId): void
    {
        if (! $this->query->isParticipant($chat, $userId)) {
            throw new CustomException(ErrorCode::FORBIDDEN, '채팅방 참여자만 메시지를 조회할 수 있습니다.');
        }
    }
}
