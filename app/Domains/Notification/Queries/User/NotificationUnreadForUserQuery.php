<?php

namespace App\Domains\Notification\Queries\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Notification\Models\NotificationInbox;

final class NotificationUnreadForUserQuery
{
    /**
     * @return array{unread_count:int, unread_event_count:int}
     */
    public function counts(AccountUser $user): array
    {
        $builder = NotificationInbox::query()
            ->where('recipient_type', NotificationInbox::RECIPIENT_USER)
            ->where('recipient_id', $user->id)
            ->whereNull('read_at');

        return [
            'unread_count' => (int) (clone $builder)->count(),
            'unread_event_count' => (int) (clone $builder)->sum('event_count'),
        ];
    }
}
