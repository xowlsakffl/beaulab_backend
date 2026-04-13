<?php

namespace App\Domains\Notification\Actions\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Notification\Queries\User\NotificationDeviceForUserQuery;

final class NotificationDeviceRegisterForUserAction
{
    public function __construct(
        private readonly NotificationDeviceForUserQuery $query,
    ) {}

    public function execute(AccountUser $user, array $payload): array
    {
        $device = $this->query->register($user, $payload);

        return [
            'device' => [
                'id' => (int) $device->id,
                'platform' => (string) $device->platform,
                'device_uuid' => $device->device_uuid,
                'app_version' => $device->app_version,
                'last_seen_at' => $device->last_seen_at?->toISOString(),
                'revoked_at' => $device->revoked_at?->toISOString(),
            ],
        ];
    }
}
