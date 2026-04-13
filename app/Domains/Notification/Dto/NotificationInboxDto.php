<?php

namespace App\Domains\Notification\Dto;

use App\Domains\Notification\Models\NotificationInbox;

/**
 * 앱 알림함 응답 DTO.
 * event_count에서 additional_count를 계산해 "외 N건" 표시를 쉽게 만든다.
 */
final readonly class NotificationInboxDto
{
    public static function fromModel(NotificationInbox $notification): array
    {
        $eventCount = max(1, (int) $notification->event_count);

        return [
            'id' => (int) $notification->id,
            'recipient_type' => (string) $notification->recipient_type,
            'recipient_id' => (int) $notification->recipient_id,
            'actor_type' => $notification->actor_type,
            'actor_id' => $notification->actor_id ? (int) $notification->actor_id : null,
            'event_type' => (string) $notification->event_type,
            'title' => $notification->title,
            'body' => $notification->body,
            'aggregation_key' => $notification->aggregation_key,
            'event_count' => $eventCount,
            'additional_count' => max(0, $eventCount - 1),
            'target_type' => $notification->target_type,
            'target_id' => $notification->target_id ? (int) $notification->target_id : null,
            'payload' => $notification->payload,
            'is_read' => $notification->isRead(),
            'read_at' => $notification->read_at?->toISOString(),
            'created_at' => $notification->created_at?->toISOString(),
            'updated_at' => $notification->updated_at?->toISOString(),
        ];
    }
}
