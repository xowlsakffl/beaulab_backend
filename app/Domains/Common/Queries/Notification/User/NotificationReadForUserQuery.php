<?php

namespace App\Domains\Common\Queries\Notification\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Models\Notification\NotificationInbox;
use Illuminate\Support\Facades\DB;

/**
 * 알림 읽음 상태 저장 Query.
 * 읽음 처리는 알림 정렬용 updated_at을 흔들지 않도록 timestamps를 끈다.
 */
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
