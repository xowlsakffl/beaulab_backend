<?php

namespace App\Domains\Common\Dto\Notification;

use App\Domains\Common\Models\Notification\NotificationInbox;

/**
 * NotificationInboxDto DTO.
 */
final readonly class NotificationInboxDto
{
    public function __construct(
        public int $id,
        public string $recipientType,
        public int $recipientId,
        public ?string $actorType,
        public ?int $actorId,
        public string $eventType,
        public ?string $title,
        public ?string $body,
        public ?string $aggregationKey,
        public int $eventCount,
        public int $additionalCount,
        public ?string $targetType,
        public ?int $targetId,
        public mixed $payload,
        public bool $isRead,
        public ?string $readAt,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fromModel(NotificationInbox $notification): self
    {
        $eventCount = max(1, (int) $notification->event_count);

        return new self(
            id: (int) $notification->id,
            recipientType: (string) $notification->recipient_type,
            recipientId: (int) $notification->recipient_id,
            actorType: $notification->actor_type,
            actorId: $notification->actor_id ? (int) $notification->actor_id : null,
            eventType: (string) $notification->event_type,
            title: $notification->title,
            body: $notification->body,
            aggregationKey: $notification->aggregation_key,
            eventCount: $eventCount,
            additionalCount: max(0, $eventCount - 1),
            targetType: $notification->target_type,
            targetId: $notification->target_id ? (int) $notification->target_id : null,
            payload: $notification->payload,
            isRead: $notification->isRead(),
            readAt: $notification->read_at?->toISOString(),
            createdAt: $notification->created_at?->toISOString(),
            updatedAt: $notification->updated_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'recipient_type' => $this->recipientType,
            'recipient_id' => $this->recipientId,
            'actor_type' => $this->actorType,
            'actor_id' => $this->actorId,
            'event_type' => $this->eventType,
            'title' => $this->title,
            'body' => $this->body,
            'aggregation_key' => $this->aggregationKey,
            'event_count' => $this->eventCount,
            'additional_count' => $this->additionalCount,
            'target_type' => $this->targetType,
            'target_id' => $this->targetId,
            'payload' => $this->payload,
            'is_read' => $this->isRead,
            'read_at' => $this->readAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        return $data;
    }
}
