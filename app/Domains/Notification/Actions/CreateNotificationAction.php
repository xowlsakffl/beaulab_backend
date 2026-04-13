<?php

namespace App\Domains\Notification\Actions;

use App\Domains\Notification\Events\NotificationInboxUpdated;
use App\Domains\Notification\Models\NotificationDelivery;
use App\Domains\Notification\Models\NotificationInbox;
use App\Domains\Notification\Queries\NotificationCreateQuery;

final class CreateNotificationAction
{
    public function __construct(
        private readonly NotificationCreateQuery $query,
    ) {}

    public function execute(array $payload): ?NotificationInbox
    {
        $data = $this->normalizePayload($payload);

        if (! $this->query->isInAppEnabled($data['recipient_type'], $data['recipient_id'], $data['event_type'])) {
            return null;
        }

        $notification = $this->query->store($data);

        NotificationInboxUpdated::dispatch(
            (int) $notification->id,
            (string) $notification->recipient_type,
            (int) $notification->recipient_id,
        );

        return $notification;
    }

    private function normalizePayload(array $payload): array
    {
        $recipientType = $this->normalizeRequiredString($payload['recipient_type'] ?? NotificationInbox::RECIPIENT_USER);
        $recipientId = (int) ($payload['recipient_id'] ?? 0);
        $eventType = $this->normalizeRequiredString($payload['event_type'] ?? '');
        $aggregationKey = $this->normalizeNullableString($payload['aggregation_key'] ?? null);
        $aggregate = (bool) ($payload['aggregate'] ?? true);

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
