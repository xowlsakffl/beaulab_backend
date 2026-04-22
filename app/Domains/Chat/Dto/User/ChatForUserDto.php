<?php

namespace App\Domains\Chat\Dto\User;

use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Models\ChatParticipant;
use Illuminate\Support\Collection;

/**
 * ChatForUserDto DTO.
 */
final readonly class ChatForUserDto
{
    public function __construct(
        public int $id,
        public string $status,
        public ?int $lastMessageId,
        public ?string $lastMessageAt,
        public ?array $lastMessage,
        public int $unreadCount,
        public bool $notificationsEnabled,
        public ?int $lastReadMessageId,
        public ?string $lastReadAt,
        public ?int $otherLastReadMessageId,
        public ?string $otherLastReadAt,
        public ?int $deletedUntilMessageId,
        public ?string $deletedAt,
        public ?array $otherUser,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $closedAt,
    ) {}

    public static function fromModel(Chat $chat, int $currentUserId): self
    {
        $participants = self::participants($chat);
        $currentParticipant = $participants->first(
            static fn (ChatParticipant $participant): bool => (int) $participant->account_user_id === $currentUserId
        );
        $otherParticipant = $participants->first(
            static fn (ChatParticipant $participant): bool => (int) $participant->account_user_id !== $currentUserId
        );

        return new self(
            id: (int) $chat->id,
            status: (string) $chat->status,
            lastMessageId: $chat->last_message_id ? (int) $chat->last_message_id : null,
            lastMessageAt: $chat->last_message_at?->toISOString(),
            lastMessage: $chat->relationLoaded('lastMessage') && $chat->lastMessage
                ? ChatMessageForUserDto::fromModel($chat->lastMessage, $currentUserId)->toArray()
                : null,
            unreadCount: (int) ($chat->unread_count ?? 0),
            notificationsEnabled: $currentParticipant
                ? (bool) $currentParticipant->notifications_enabled
                : true,
            lastReadMessageId: $currentParticipant?->last_read_message_id
                ? (int) $currentParticipant->last_read_message_id
                : null,
            lastReadAt: $currentParticipant?->last_read_at?->toISOString(),
            otherLastReadMessageId: $otherParticipant?->last_read_message_id
                ? (int) $otherParticipant->last_read_message_id
                : null,
            otherLastReadAt: $otherParticipant?->last_read_at?->toISOString(),
            deletedUntilMessageId: $currentParticipant?->deleted_until_message_id
                ? (int) $currentParticipant->deleted_until_message_id
                : null,
            deletedAt: $currentParticipant?->deleted_at?->toISOString(),
            otherUser: self::otherUser($otherParticipant),
            createdAt: $chat->created_at?->toISOString(),
            updatedAt: $chat->updated_at?->toISOString(),
            closedAt: $chat->closed_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'status' => $this->status,
            'last_message_id' => $this->lastMessageId,
            'last_message_at' => $this->lastMessageAt,
            'last_message' => $this->lastMessage,
            'unread_count' => $this->unreadCount,
            'notifications_enabled' => $this->notificationsEnabled,
            'last_read_message_id' => $this->lastReadMessageId,
            'last_read_at' => $this->lastReadAt,
            'other_last_read_message_id' => $this->otherLastReadMessageId,
            'other_last_read_at' => $this->otherLastReadAt,
            'deleted_until_message_id' => $this->deletedUntilMessageId,
            'deleted_at' => $this->deletedAt,
            'other_user' => $this->otherUser,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'closed_at' => $this->closedAt,
        ];

        return $data;
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

    private static function otherUser(?ChatParticipant $participant): ?array
    {
        if (! $participant || ! $participant->relationLoaded('accountUser') || ! $participant->accountUser) {
            return null;
        }

        return [
            'id' => (int) $participant->accountUser->id,
            'nickname' => (string) $participant->accountUser->nickname,
            'email' => (string) $participant->accountUser->email,
        ];
    }
}
