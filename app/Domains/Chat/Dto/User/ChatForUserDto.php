<?php

namespace App\Domains\Chat\Dto\User;

use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Models\ChatParticipant;
use Illuminate\Support\Collection;

/**
 * 앱 채팅방 응답 DTO.
 * 현재 사용자 기준 상대방 정보, 알림 설정, 읽음 상태, 미읽음 수를 함께 내려준다.
 */
final readonly class ChatForUserDto
{
    public static function fromModel(Chat $chat, int $currentUserId): array
    {
        $participants = self::participants($chat);
        $currentParticipant = $participants->first(
            static fn (ChatParticipant $participant): bool => (int) $participant->account_user_id === $currentUserId
        );
        $otherParticipant = $participants->first(
            static fn (ChatParticipant $participant): bool => (int) $participant->account_user_id !== $currentUserId
        );

        return [
            'id' => (int) $chat->id,
            'status' => (string) $chat->status,
            'last_message_id' => $chat->last_message_id ? (int) $chat->last_message_id : null,
            'last_message_at' => $chat->last_message_at?->toISOString(),
            'last_message' => $chat->relationLoaded('lastMessage') && $chat->lastMessage
                ? ChatMessageForUserDto::fromModel($chat->lastMessage, $currentUserId)
                : null,
            'unread_count' => (int) ($chat->unread_count ?? 0),
            'notifications_enabled' => $currentParticipant
                ? (bool) $currentParticipant->notifications_enabled
                : true,
            'last_read_message_id' => $currentParticipant?->last_read_message_id
                ? (int) $currentParticipant->last_read_message_id
                : null,
            'last_read_at' => $currentParticipant?->last_read_at?->toISOString(),
            'other_last_read_message_id' => $otherParticipant?->last_read_message_id
                ? (int) $otherParticipant->last_read_message_id
                : null,
            'other_last_read_at' => $otherParticipant?->last_read_at?->toISOString(),
            'deleted_until_message_id' => $currentParticipant?->deleted_until_message_id
                ? (int) $currentParticipant->deleted_until_message_id
                : null,
            'deleted_at' => $currentParticipant?->deleted_at?->toISOString(),
            'other_user' => $otherParticipant && $otherParticipant->relationLoaded('accountUser') && $otherParticipant->accountUser
                ? [
                    'id' => (int) $otherParticipant->accountUser->id,
                    'nickname' => (string) $otherParticipant->accountUser->nickname,
                    'email' => (string) $otherParticipant->accountUser->email,
                ]
                : null,
            'created_at' => $chat->created_at?->toISOString(),
            'updated_at' => $chat->updated_at?->toISOString(),
            'closed_at' => $chat->closed_at?->toISOString(),
        ];
    }

    /**
     * @return Collection<int, ChatParticipant>
     */
    private static function participants(Chat $chat): Collection
    {
        if (! $chat->relationLoaded('participants')) {
            return collect();
        }

        return $chat->participants;
    }
}
