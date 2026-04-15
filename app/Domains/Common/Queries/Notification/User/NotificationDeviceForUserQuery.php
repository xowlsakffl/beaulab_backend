<?php

namespace App\Domains\Common\Queries\Notification\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Models\Notification\NotificationDevice;
use App\Domains\Common\Models\Notification\NotificationInbox;
use Illuminate\Support\Facades\DB;

/**
 * 푸시 디바이스 저장/폐기 Query.
 * 긴 Web Push endpoint를 고려해 원문 token 대신 hash로 unique 처리를 한다.
 */
final class NotificationDeviceForUserQuery
{
    public function register(AccountUser $user, array $payload): NotificationDevice
    {
        return DB::transaction(function () use ($user, $payload): NotificationDevice {
            $deviceUuid = $this->normalizeNullableString($payload['device_uuid'] ?? null);
            $platform = mb_strtoupper((string) $payload['platform']);
            $pushToken = trim((string) $payload['push_token']);
            $pushTokenHash = hash('sha256', $pushToken);

            if ($deviceUuid !== null) {
                NotificationDevice::query()
                    ->where('owner_type', NotificationInbox::RECIPIENT_USER)
                    ->where('owner_id', $user->id)
                    ->where('platform', $platform)
                    ->where('device_uuid', $deviceUuid)
                    ->where('push_token_hash', '!=', $pushTokenHash)
                    ->whereNull('revoked_at')
                    ->update([
                        'revoked_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            return NotificationDevice::updateOrCreate(
                ['push_token_hash' => $pushTokenHash],
                [
                    'owner_type' => NotificationInbox::RECIPIENT_USER,
                    'owner_id' => $user->id,
                    'platform' => $platform,
                    'device_uuid' => $deviceUuid,
                    'push_token' => $pushToken,
                    'app_version' => $this->normalizeNullableString($payload['app_version'] ?? null),
                    'last_seen_at' => now(),
                    'revoked_at' => null,
                    'metadata' => $payload['metadata'] ?? null,
                ],
            );
        });
    }

    public function revoke(AccountUser $user, string $pushToken): int
    {
        $pushTokenHash = hash('sha256', trim($pushToken));

        return (int) NotificationDevice::query()
            ->where('owner_type', NotificationInbox::RECIPIENT_USER)
            ->where('owner_id', $user->id)
            ->where('push_token_hash', $pushTokenHash)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'updated_at' => now(),
            ]);
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
