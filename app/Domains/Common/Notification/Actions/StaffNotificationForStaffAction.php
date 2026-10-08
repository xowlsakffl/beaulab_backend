<?php

namespace App\Domains\Common\Notification\Actions;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Notification\Models\NotificationInbox;
use App\Domains\Common\Notification\Models\NotificationPreference;
use App\Domains\Common\Notification\Support\StaffNotificationState;
use App\Domains\Common\Notification\Support\StaffNotificationTypeRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StaffNotificationForStaffAction
{
    public function __construct(private readonly StaffNotificationTypeRegistry $registry) {}

    public function listing(AccountStaff $staff, array $input): array
    {
        $filters = $this->filters($input);
        $limit = max(1, min(50, (int) ($input['limit'] ?? 20)));

        return DB::transaction(function () use ($staff, $input, $filters, $limit): array {
            StaffNotificationState::lock((int) $staff->id);
            $snapshot = $this->snapshot($staff, $filters);
            $before = null;
            $beforeReceipt = null;
            if (! empty($input['cursor'])) {
                $cursor = $this->decode($staff, $input['cursor'], 'cursor');
                if ($cursor['filters'] !== $filters) {
                    $this->invalidToken('cursor');
                }
                $snapshot = $cursor['snapshot'];
                $before = (int) $cursor['before_id'];
                $beforeReceipt = (int) $cursor['before_receipt_id'];
            }
            $rows = $this->filtered($staff, $snapshot)
                ->when($before !== null, function (Builder $query) use ($before, $beforeReceipt): void {
                    $query->where(function (Builder $query) use ($before, $beforeReceipt): void {
                        $query->whereRaw('COALESCE(notification_activity.receipt_id, 0) < ?', [$beforeReceipt])
                            ->orWhere(function (Builder $query) use ($before, $beforeReceipt): void {
                                $query->whereRaw('COALESCE(notification_activity.receipt_id, 0) = ?', [$beforeReceipt])
                                    ->where('notification_inboxes.id', '<', $before);
                            });
                    });
                })
                ->orderByDesc('activity_id')->orderByDesc('notification_inboxes.id')->limit($limit + 1)->get();
            $hasMore = $rows->count() > $limit;
            $rows = $rows->take($limit);
            $next = $hasMore ? $this->encode($staff, 'cursor', [
                'filters' => $filters, 'snapshot' => $snapshot, 'before_id' => (int) $rows->last()->id,
                'before_receipt_id' => (int) $rows->last()->activity_id,
            ]) : null;

            return [
                'items' => $this->registry->items($rows),
                'next_cursor' => $next,
                'unread_count' => $this->count($staff),
                'read_all_token' => $this->encode($staff, 'read_all_token', $snapshot),
                'types' => $this->preferences($staff)['preferences'],
            ];
        });
    }

    public function unread(AccountStaff $staff, array $input = []): array
    {
        return DB::transaction(function () use ($staff, $input): array {
            StaffNotificationState::lock((int) $staff->id);

            return [
                'unread_count' => $this->count($staff),
                'read_all_token' => $this->encode($staff, 'read_all_token', $this->snapshot($staff, $this->filters($input))),
            ];
        });
    }

    public function read(AccountStaff $staff, int $id, int $expectedEventCount, ?int $latestMessageId): array
    {
        return DB::transaction(function () use ($staff, $id, $expectedEventCount, $latestMessageId): array {
            StaffNotificationState::lock((int) $staff->id);
            $inbox = $this->registry->visibleQuery($staff)->whereKey($id)->lockForUpdate()->firstOrFail();
            $changed = $inbox->read_at === null && (int) $inbox->event_count === $expectedEventCount
                && ($latestMessageId === null || (int) ($inbox->payload['message_id'] ?? 0) === $latestMessageId);
            if ($changed) {
                $this->markRead($inbox);
                StaffNotificationState::changed((int) $staff->id);
            }

            return ['notification' => $this->registry->items(collect([$inbox]))[0], 'changed' => $changed, 'unread_count' => $this->count($staff)];
        }, 3);
    }

    public function readAll(AccountStaff $staff, string $token): array
    {
        $snapshot = $this->decode($staff, $token, 'read_all_token');

        return DB::transaction(function () use ($staff, $snapshot): array {
            StaffNotificationState::lock((int) $staff->id);
            $count = 0;
            $this->filtered($staff, $snapshot)->whereNull('read_at')
                ->where(function (Builder $query) use ($snapshot): void {
                    $query->whereNull('payload->last_receipt_id')->orWhere('payload->last_receipt_id', '<=', $snapshot['receipt_id']);
                })->chunkById(200, function ($rows) use (&$count): void {
                    foreach ($rows as $inbox) {
                        $this->markRead($inbox);
                        $count++;
                    }
                }, 'notification_inboxes.id', 'id');
            if ($count > 0) {
                StaffNotificationState::changed((int) $staff->id);
            }

            return ['updated_count' => $count, 'unread_count' => $this->count($staff)];
        }, 3);
    }

    public function preferences(AccountStaff $staff): array
    {
        $preferences = NotificationPreference::query()->where('owner_type', NotificationInbox::RECIPIENT_STAFF)
            ->where('owner_id', $staff->id)->get()->keyBy('event_type');
        $items = [];
        foreach ($this->registry->allowedTypes($staff) as $eventType) {
            if (in_array($eventType, config('staff_notifications.hidden_types', []), true)) {
                continue;
            }
            $definition = $this->registry->definition($eventType);
            $items[] = ['event_type' => $eventType, 'title' => $definition['title'], 'target_type' => $definition['target_type'], 'in_app' => (bool) ($preferences->get($eventType)?->in_app ?? $this->registry->defaultEnabled($eventType))];
        }

        return ['preferences' => $items];
    }

    public function updatePreferences(AccountStaff $staff, array $preferences): array
    {
        return DB::transaction(function () use ($staff, $preferences): array {
            StaffNotificationState::lock((int) $staff->id);
            foreach ($preferences as $preference) {
                abort_unless($this->registry->allows($staff, $preference['event_type']), 403);
                NotificationPreference::query()->updateOrCreate(
                    ['owner_type' => NotificationInbox::RECIPIENT_STAFF, 'owner_id' => $staff->id, 'event_type' => $preference['event_type']],
                    ['in_app' => $preference['in_app'], 'push' => false, 'email' => false],
                );
            }
            StaffNotificationState::changed((int) $staff->id);

            return $this->preferences($staff);
        }, 3);
    }

    private function markRead(NotificationInbox $inbox): void
    {
        $inbox->forceFill(['read_at' => now(), 'open_aggregation_key' => null])->save();
    }

    private function count(AccountStaff $staff): int
    {
        return $this->registry->visibleQuery($staff)->whereNull('read_at')->count();
    }

    private function filtered(AccountStaff $staff, array $snapshot): Builder
    {
        $filters = $snapshot['filters'];
        // 스냅샷 이후 집계가 갱신돼도 기존 페이지의 정렬 키는 움직이지 않는다.
        $activity = DB::table('staff_notification_receipts')->where('staff_id', $staff->id)
            ->where('id', '<=', $snapshot['receipt_id'])->whereNotNull('notification_inbox_id')
            ->groupBy('notification_inbox_id')
            ->selectRaw('notification_inbox_id, MAX(id) AS receipt_id, MAX(created_at) AS occurred_at');

        return $this->registry->visibleQuery($staff)
            ->leftJoinSub($activity, 'notification_activity', 'notification_activity.notification_inbox_id', '=', 'notification_inboxes.id')
            ->select('notification_inboxes.*')->selectRaw('COALESCE(notification_activity.receipt_id, 0) AS activity_id')
            ->where('notification_inboxes.id', '<=', $snapshot['inbox_id'])
            ->when($filters['unread_only'], fn (Builder $query) => $query->whereNull('read_at'))
            ->when($filters['event_type'] !== null, fn (Builder $query) => $query->where('event_type', $filters['event_type']))
            ->when($filters['start_date'] !== null, fn (Builder $query) => $query->whereRaw('COALESCE(notification_activity.occurred_at, notification_inboxes.created_at) >= ?', [$filters['start_date'].' 00:00:00']))
            ->when($filters['end_date'] !== null, fn (Builder $query) => $query->whereRaw('COALESCE(notification_activity.occurred_at, notification_inboxes.created_at) <= ?', [$filters['end_date'].' 23:59:59']));
    }

    private function filters(array $input): array
    {
        return [
            'unread_only' => (bool) ($input['unread_only'] ?? false),
            'event_type' => $input['event_type'] ?? null,
            'start_date' => $input['start_date'] ?? null,
            'end_date' => $input['end_date'] ?? null,
        ];
    }

    private function snapshot(AccountStaff $staff, array $filters): array
    {
        return [
            'filters' => $filters,
            'inbox_id' => (int) $this->registry->visibleQuery($staff)->max('id'),
            'receipt_id' => (int) DB::table('staff_notification_receipts')->where('staff_id', $staff->id)->max('id'),
        ];
    }

    private function permissionHash(AccountStaff $staff): string
    {
        $types = $this->registry->allowedTypes($staff);
        sort($types);

        return hash('sha256', json_encode($types, JSON_THROW_ON_ERROR));
    }

    private function encode(AccountStaff $staff, string $kind, array $values): string
    {
        return Crypt::encryptString(json_encode($values + [
            'kind' => $kind, 'staff_id' => (int) $staff->id,
            'permissions' => $this->permissionHash($staff), 'expires_at' => now()->addDay()->timestamp,
        ], JSON_THROW_ON_ERROR));
    }

    private function decode(AccountStaff $staff, string $token, string $kind): array
    {
        try {
            $data = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            $this->invalidToken($kind);
        }
        if (! is_array($data) || ($data['kind'] ?? null) !== $kind || ($data['staff_id'] ?? null) !== (int) $staff->id
            || ($data['permissions'] ?? null) !== $this->permissionHash($staff) || ($data['expires_at'] ?? 0) < now()->timestamp) {
            $this->invalidToken($kind);
        }

        return $data;
    }

    private function invalidToken(string $field): never
    {
        throw ValidationException::withMessages([$field => '알림 조회 조건 또는 권한이 변경되었거나 유효기간이 지났습니다. 목록을 다시 조회해 주세요.']);
    }
}
