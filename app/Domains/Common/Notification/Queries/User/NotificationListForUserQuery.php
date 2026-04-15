<?php

namespace App\Domains\Common\Notification\Queries\User;

use App\Domains\Common\Notification\Models\NotificationDelivery;
use App\Domains\Common\Notification\Models\NotificationInbox;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 앱 사용자 알림 목록 Query.
 * unread, event_type, target 필터를 적용하고 최근 갱신된 알림 묶음부터 정렬한다.
 */
final class NotificationListForUserQuery
{
    public function paginate(int $userId, array $filters): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 50));

        $builder = NotificationInbox::query()
            ->where('recipient_type', NotificationInbox::RECIPIENT_USER)
            ->where('recipient_id', $userId)
            ->whereHas('deliveries', function ($query): void {
                $query->where('channel', NotificationDelivery::CHANNEL_IN_APP);
            })
            ->where(function ($query) use ($userId): void {
                $query
                    ->whereNull('aggregation_key')
                    ->orWhereIn('id', function ($query) use ($userId): void {
                        $query
                            ->selectRaw('MAX(notification_inboxes.id)')
                            ->from('notification_inboxes')
                            ->join('notification_deliveries', 'notification_deliveries.notification_inbox_id', '=', 'notification_inboxes.id')
                            ->where('recipient_type', NotificationInbox::RECIPIENT_USER)
                            ->where('recipient_id', $userId)
                            ->where('notification_deliveries.channel', NotificationDelivery::CHANNEL_IN_APP)
                            ->whereNotNull('aggregation_key')
                            ->groupBy('aggregation_key');
                    });
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ((bool) ($filters['unread_only'] ?? false)) {
            $builder->whereNull('read_at');
        }

        if (! empty($filters['event_type'])) {
            $builder->where('event_type', (string) $filters['event_type']);
        }

        if (! empty($filters['target_type'])) {
            $builder->where('target_type', (string) $filters['target_type']);
        }

        if (! empty($filters['target_id'])) {
            $builder->where('target_id', (int) $filters['target_id']);
        }

        return $builder->paginate($perPage)->withQueryString();
    }
}
