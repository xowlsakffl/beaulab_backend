<?php

namespace App\Domains\Common\Notification\Dto;

use App\Domains\Common\Notification\Models\NotificationPreference;

/**
 * NotificationPreferenceDto DTO.
 */
final readonly class NotificationPreferenceDto
{
    public function __construct(
        public string $eventType,
        public bool $inApp,
        public bool $push,
        public bool $email,
        public mixed $metadata,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fromModel(NotificationPreference $preference): self
    {
        return new self(
            eventType: (string) $preference->event_type,
            inApp: (bool) $preference->in_app,
            push: (bool) $preference->push,
            email: (bool) $preference->email,
            metadata: $preference->metadata,
            createdAt: $preference->created_at?->toISOString(),
            updatedAt: $preference->updated_at?->toISOString(),
        );
    }

    public static function default(string $eventType): self
    {
        return new self(
            eventType: $eventType,
            inApp: true,
            push: true,
            email: false,
            metadata: null,
            createdAt: null,
            updatedAt: null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'event_type' => $this->eventType,
            'in_app' => $this->inApp,
            'push' => $this->push,
            'email' => $this->email,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        return $data;
    }
}
