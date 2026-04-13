<?php

namespace App\Domains\Notification\Queries\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Notification\Models\NotificationInbox;
use Illuminate\Support\Facades\DB;

final class NotificationReadForUserQuery
{
    public function read(NotificationInbox $notification): NotificationInbox
    {
        if ($notification->isRead()) {
            return $notification->refresh();
        }

        $notification->timestamps = false;

        try {
            $notification->forceFill([
                'read_at' => now(),
                'open_aggregation_key' => null,
            ])->save();
        } finally {
            $notification->timestamps = true;
        }

        return $notification->refresh();
    }

    public function readAll(AccountUser $user): int
    {
        return (int) DB::table('notification_inboxes')
            ->where('recipient_type', NotificationInbox::RECIPIENT_USER)
            ->where('recipient_id', (int) $user->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'open_aggregation_key' => null,
            ]);
    }
}
