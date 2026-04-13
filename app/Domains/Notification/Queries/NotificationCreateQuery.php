<?php

namespace App\Domains\Notification\Queries;

use App\Domains\Notification\Models\NotificationDelivery;
use App\Domains\Notification\Models\NotificationDevice;
use App\Domains\Notification\Models\NotificationInbox;
use App\Domains\Notification\Models\NotificationPreference;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

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

    private function storeOnce(array $data): NotificationInbox
    {
        return DB::transaction(function () use ($data): NotificationInbox {
            $notification = null;

            if ($data['open_aggregation_key'] !== null) {
                $notification = NotificationInbox::query()
                    ->where('recipient_type', $data['recipient_type'])
                    ->where('recipient_id', $data['recipient_id'])
                    ->where('open_aggregation_key', $data['open_aggregation_key'])
                    ->lockForUpdate()
                    ->first();
            }

            if ($notification instanceof NotificationInbox) {
                $notification->forceFill([
                    'actor_type' => $data['actor_type'],
                    'actor_id' => $data['actor_id'],
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'event_count' => max(1, (int) $notification->event_count) + 1,
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

            $this->syncDelivery($notification, NotificationDelivery::CHANNEL_IN_APP);

            if (
                $this->isPushRequested($data)
                && $this->isPushEnabled($data['recipient_type'], $data['recipient_id'], $data['event_type'])
                && $this->hasActivePushDevice($data['recipient_type'], $data['recipient_id'])
            ) {
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

    private function shouldRetryOpenAggregationRace(QueryException $e, array $data, int $attempt): bool
    {
        return $attempt === 0
            && $data['open_aggregation_key'] !== null
            && (string) $e->getCode() === '23000';
    }
}
