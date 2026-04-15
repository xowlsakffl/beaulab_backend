<?php

namespace App\Domains\Common\Actions\Notification;

use App\Domains\Common\Events\Notification\NotificationInboxUpdated;
use App\Domains\Common\Jobs\Notification\SendPushNotificationDeliveryJob;
use App\Domains\Common\Models\Notification\NotificationDelivery;
use App\Domains\Common\Models\Notification\NotificationInbox;
use App\Domains\Common\Queries\Notification\NotificationCreateQuery;

/**
 * 공통 알림 생성 유스케이스.
 * payload를 표준 형태로 정규화하고, 저장 후 실시간 알림 이벤트를 발행한다.
 */
final class CreateNotificationAction
{
    public function __construct(
        private readonly NotificationCreateQuery $query,
    ) {}

    public function execute(array $payload): ?NotificationInbox
    {
        $data = $this->normalizePayload($payload);
        $data['channels'] = $this->enabledChannels($data);

        if ($data['channels'] === []) {
            return null;
        }

        $data = $this->scopeOpenAggregationKeyByChannels($data);
        $notification = $this->query->store($data);

        if (in_array(NotificationDelivery::CHANNEL_IN_APP, $data['channels'], true)) {
            NotificationInboxUpdated::dispatch(
                (int) $notification->id,
                (string) $notification->recipient_type,
                (int) $notification->recipient_id,
            );
        }

        $this->dispatchPushDelivery($notification);

        return $notification;
    }

    private function enabledChannels(array $data): array
    {
        return collect($data['channels'])
            ->filter(function (string $channel) use ($data): bool {
                return match ($channel) {
                    NotificationDelivery::CHANNEL_IN_APP => $this->query->isInAppEnabled(
                        $data['recipient_type'],
                        $data['recipient_id'],
                        $data['event_type'],
                    ),
                    NotificationDelivery::CHANNEL_PUSH => $this->query->isPushDeliverable(
                        $data['recipient_type'],
                        $data['recipient_id'],
                        $data['event_type'],
                    ),
                    default => true,
                };
            })
            ->values()
            ->all();
    }

    private function scopeOpenAggregationKeyByChannels(array $data): array
    {
        if ($data['open_aggregation_key'] === null) {
            return $data;
        }

        $data['open_aggregation_key'] = sprintf(
            'channels:%s:%s',
            in_array(NotificationDelivery::CHANNEL_IN_APP, $data['channels'], true) ? 'in_app' : 'non_in_app',
            sha1($data['open_aggregation_key']),
        );

        return $data;
    }

    private function dispatchPushDelivery(NotificationInbox $notification): void
    {
        $pushDelivery = $notification->deliveries()
            ->where('channel', NotificationDelivery::CHANNEL_PUSH)
            ->where('status', NotificationDelivery::STATUS_PENDING)
            ->first();

        if ($pushDelivery instanceof NotificationDelivery) {
            SendPushNotificationDeliveryJob::dispatch((int) $pushDelivery->id);
        }
    }

    private function normalizePayload(array $payload): array
    {
        $recipientType = $this->normalizeRequiredString($payload['recipient_type'] ?? NotificationInbox::RECIPIENT_USER);
        $recipientId = (int) ($payload['recipient_id'] ?? 0);
        $eventType = $this->normalizeRequiredString($payload['event_type'] ?? '');
        $aggregationKey = $this->normalizeNullableString($payload['aggregation_key'] ?? null);
        $aggregate = (bool) ($payload['aggregate'] ?? true);

        // aggregation_key가 있는 unread 알림은 하나의 row로 묶어 "외 N건" 표시가 가능하게 한다.
        if ($recipientId < 1) {
            throw new \InvalidArgumentException('Notification payload contains an invalid recipient id.');
        }

        return [
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'actor_type' => $this->normalizeNullableString($payload['actor_type'] ?? null),
            'actor_id' => isset($payload['actor_id']) ? (int) $payload['actor_id'] : null,
            'event_type' => $eventType,
            'title' => $this->normalizeNullableString($payload['title'] ?? null),
            'body' => $this->normalizeNullableString($payload['body'] ?? null),
            'aggregation_key' => $aggregationKey,
            'open_aggregation_key' => $aggregate ? $aggregationKey : null,
            'target_type' => $this->normalizeNullableString($payload['target_type'] ?? null),
            'target_id' => isset($payload['target_id']) ? (int) $payload['target_id'] : null,
            'payload' => isset($payload['payload']) && is_array($payload['payload']) ? $payload['payload'] : null,
            'channels' => $this->normalizeChannels($payload['channels'] ?? [NotificationDelivery::CHANNEL_IN_APP]),
        ];
    }

    private function normalizeChannels(mixed $channels): array
    {
        if (! is_array($channels)) {
            return [NotificationDelivery::CHANNEL_IN_APP];
        }

        $allowed = [
            NotificationDelivery::CHANNEL_IN_APP,
            NotificationDelivery::CHANNEL_PUSH,
            NotificationDelivery::CHANNEL_EMAIL,
            NotificationDelivery::CHANNEL_WEB,
        ];

        return collect($channels)
            ->map(fn (mixed $channel): string => mb_strtoupper(trim((string) $channel)))
            ->filter(fn (string $channel): bool => in_array($channel, $allowed, true))
            ->unique()
            ->values()
            ->all() ?: [NotificationDelivery::CHANNEL_IN_APP];
    }

    private function normalizeRequiredString(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            throw new \InvalidArgumentException('Notification payload contains an empty required string.');
        }

        return $value;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
