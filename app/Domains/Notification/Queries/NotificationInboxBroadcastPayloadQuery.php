<?php

namespace App\Domains\Notification\Queries;

use App\Domains\Notification\Dto\NotificationInboxDto;
use App\Domains\Notification\Models\NotificationInbox;

/**
 * Reverb 알림 이벤트 payload 조회 전용 Query.
 * 큐에서 실행될 때 최신 notification row를 읽어 DTO 형태로 변환한다.
 */
final class NotificationInboxBroadcastPayloadQuery
{
    public function payload(int $notificationId): ?array
    {
        $notification = NotificationInbox::query()->find($notificationId);

        return $notification instanceof NotificationInbox
            ? NotificationInboxDto::fromModel($notification)
            : null;
    }
}
