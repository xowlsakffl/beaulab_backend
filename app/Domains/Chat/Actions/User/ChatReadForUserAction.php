<?php

namespace App\Domains\Chat\Actions\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Dto\User\ChatForUserDto;
use App\Domains\Chat\Events\ChatReadStatusUpdated;
use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Models\ChatParticipant;
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
        $participant = $this->currentParticipant($chat, (int) $user->id);

        ChatReadStatusUpdated::dispatch(
            (int) $chat->id,
            (int) $user->id,
            $participant?->last_read_message_id ? (int) $participant->last_read_message_id : null,
            $participant?->last_read_at?->toISOString(),
        );

        return [
            'chat' => ChatForUserDto::fromModel($chat, (int) $user->id),
        ];
    }

    private function currentParticipant(Chat $chat, int $userId): ?ChatParticipant
    {
        if (! $chat->relationLoaded('participants')) {
            return null;
        }

        return $chat->participants->first(
            static fn (ChatParticipant $participant): bool => (int) $participant->account_user_id === $userId
        );
    }
}
