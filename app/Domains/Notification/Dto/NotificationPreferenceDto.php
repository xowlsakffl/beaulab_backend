<?php

namespace App\Domains\Notification\Dto;

use App\Domains\Notification\Models\NotificationPreference;

/**
 * 이벤트별 알림 설정 응답 DTO.
 * DB row가 없는 기본 이벤트도 default 응답으로 내려줄 수 있게 분리한다.
 */
final readonly class NotificationPreferenceDto
{
    public static function fromModel(NotificationPreference $preference): array
    {
        return [
            'event_type' => (string) $preference->event_type,
            'in_app' => (bool) $preference->in_app,
            'push' => (bool) $preference->push,
            'email' => (bool) $preference->email,
            'metadata' => $preference->metadata,
            'created_at' => $preference->created_at?->toISOString(),
            'updated_at' => $preference->updated_at?->toISOString(),
        ];
    }

    public static function default(string $eventType): array
    {
        return [
            'event_type' => $eventType,
            'in_app' => true,
            'push' => true,
            'email' => false,
            'metadata' => null,
            'created_at' => null,
            'updated_at' => null,
        ];
    }
}
