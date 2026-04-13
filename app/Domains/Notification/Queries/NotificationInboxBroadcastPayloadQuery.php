<?php

namespace App\Domains\Notification\Queries;

use App\Domains\Notification\Dto\NotificationInboxDto;
use App\Domains\Notification\Models\NotificationInbox;

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
