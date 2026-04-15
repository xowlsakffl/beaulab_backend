<?php

namespace App\Domains\Common\Queries\Notification\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Models\Notification\NotificationDelivery;
use App\Domains\Common\Models\Notification\NotificationInbox;

/**
 * 앱 사용자 unread 알림 카운트 Query.
 * unread_count는 알림 묶음 수, unread_event_count는 묶음 안의 실제 이벤트 수 합계다.
 */
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
            ->whereHas('deliveries', function ($query): void {
                $query->where('channel', NotificationDelivery::CHANNEL_IN_APP);
            })
            ->whereNull('read_at');

        return [
            'unread_count' => (int) (clone $builder)->count(),
            'unread_event_count' => (int) (clone $builder)->sum('event_count'),
        ];
    }
}
