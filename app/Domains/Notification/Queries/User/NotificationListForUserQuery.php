<?php

namespace App\Domains\Notification\Queries\User;

use App\Domains\Notification\Models\NotificationInbox;
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
