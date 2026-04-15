<?php

namespace App\Domains\Common\Notification\Actions\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Notification\Queries\User\NotificationDeviceForUserQuery;

/**
 * 앱/웹 푸시 디바이스 등록 유스케이스.
 * 토큰 원문은 저장하되 중복 판단은 SHA-256 hash 기준으로 Query에서 처리한다.
 */
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
