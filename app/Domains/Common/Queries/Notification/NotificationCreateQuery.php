<?php

namespace App\Domains\Common\Queries\Notification;

use App\Domains\Common\Models\Notification\NotificationDelivery;
use App\Domains\Common\Models\Notification\NotificationDevice;
use App\Domains\Common\Models\Notification\NotificationInbox;
use App\Domains\Common\Models\Notification\NotificationPreference;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * 알림 생성/집계 저장 Query.
 * unread 상태의 같은 aggregation_key가 있으면 insert 대신 event_count를 증가시킨다.
 */
final class NotificationCreateQuery
{
    public function store(array $data): NotificationInbox
    {
        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                return $this->storeOnce($data);
            } catch (QueryException $e) {
                if (! $this->shouldRetryOpenAggregationRace($e, $data, $attempt)) {
                    throw $e;
                }
            }
        }

        return $this->storeOnce($data);
    }

    public function isInAppEnabled(string $ownerType, int $ownerId, string $eventType): bool
    {
        $preference = $this->preference($ownerType, $ownerId, $eventType);

        return $preference ? (bool) $preference->in_app : true;
    }

    public function isPushDeliverable(string $ownerType, int $ownerId, string $eventType): bool
    {
        return $this->isPushEnabled($ownerType, $ownerId, $eventType)
            && $this->hasActivePushDevice($ownerType, $ownerId);
    }

    private function storeOnce(array $data): NotificationInbox
    {
        return DB::transaction(function () use ($data): NotificationInbox {
            $notification = null;

            // unread 집계 버킷만 갱신한다. 읽음 처리된 과거 row는 다시 열지 않는다.
            if ($data['open_aggregation_key'] !== null) {
                $notification = NotificationInbox::query()
                    ->where('recipient_type', $data['recipient_type'])
                    ->where('recipient_id', $data['recipient_id'])
                    ->where('open_aggregation_key', $data['open_aggregation_key'])
                    ->lockForUpdate()
                    ->first();
            }

            if ($notification instanceof NotificationInbox) {
                $eventCount = $notification->isRead()
                    ? 1
                    : max(1, (int) $notification->event_count) + 1;

                $notification->forceFill([
                    'actor_type' => $data['actor_type'],
                    'actor_id' => $data['actor_id'],
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'open_aggregation_key' => $data['open_aggregation_key'],
                    'event_count' => $eventCount,
                    'target_type' => $data['target_type'],
                    'target_id' => $data['target_id'],
                    'payload' => $data['payload'],
                    'read_at' => null,
                ])->save();
            } else {
                $notification = NotificationInbox::create([
                    'recipient_type' => $data['recipient_type'],
                    'recipient_id' => $data['recipient_id'],
                    'actor_type' => $data['actor_type'],
                    'actor_id' => $data['actor_id'],
                    'event_type' => $data['event_type'],
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'aggregation_key' => $data['aggregation_key'],
                    'open_aggregation_key' => $data['open_aggregation_key'],
                    'event_count' => 1,
                    'target_type' => $data['target_type'],
                    'target_id' => $data['target_id'],
                    'payload' => $data['payload'],
                ]);
            }

            if ($this->isInAppRequested($data)) {
                $this->syncDelivery($notification, NotificationDelivery::CHANNEL_IN_APP);
            }

            if (
                $this->isPushRequested($data)
                && $this->isPushEnabled($data['recipient_type'], $data['recipient_id'], $data['event_type'])
                && $this->hasActivePushDevice($data['recipient_type'], $data['recipient_id'])
            ) {
                // 실제 FCM/APNs 발송 워커가 가져갈 수 있도록 delivery만 PENDING으로 남긴다.
                $this->syncDelivery($notification, NotificationDelivery::CHANNEL_PUSH);
            }

            return $notification->refresh();
        });
    }

    private function syncDelivery(NotificationInbox $notification, string $channel): void
    {
        $delivery = NotificationDelivery::firstOrNew([
            'notification_inbox_id' => $notification->id,
            'channel' => $channel,
        ]);

        if ($channel === NotificationDelivery::CHANNEL_IN_APP) {
            $delivery->fill([
                'status' => NotificationDelivery::STATUS_SENT,
                'provider' => NotificationDelivery::PROVIDER_REVERB,
                'attempted_at' => now(),
                'delivered_at' => now(),
                'failed_at' => null,
                'error_message' => null,
            ])->save();

            return;
        }

        $delivery->fill([
            'status' => NotificationDelivery::STATUS_PENDING,
            'provider' => null,
            'attempted_at' => null,
            'delivered_at' => null,
            'failed_at' => null,
            'error_message' => null,
        ])->save();
    }

    private function isPushEnabled(string $ownerType, int $ownerId, string $eventType): bool
    {
        $preference = $this->preference($ownerType, $ownerId, $eventType);

        return $preference ? (bool) $preference->push : true;
    }

    private function hasActivePushDevice(string $ownerType, int $ownerId): bool
    {
        return NotificationDevice::query()
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->whereNull('revoked_at')
            ->exists();
    }

    private function preference(string $ownerType, int $ownerId, string $eventType): ?NotificationPreference
    {
        return NotificationPreference::query()
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->where('event_type', $eventType)
            ->first();
    }

    private function isPushRequested(array $data): bool
    {
        return in_array(NotificationDelivery::CHANNEL_PUSH, $data['channels'], true);
    }

    private function isInAppRequested(array $data): bool
    {
        return in_array(NotificationDelivery::CHANNEL_IN_APP, $data['channels'], true);
    }

    private function shouldRetryOpenAggregationRace(QueryException $e, array $data, int $attempt): bool
    {
        return $attempt === 0
            && $data['open_aggregation_key'] !== null
            && (string) $e->getCode() === '23000';
    }
}
