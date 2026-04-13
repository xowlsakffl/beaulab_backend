<?php

namespace App\Domains\Notification\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Notification\Queries\User\NotificationDeviceForUserQuery;

final class NotificationDeviceRevokeForUserAction
{
    public function __construct(
        private readonly NotificationDeviceForUserQuery $query,
    ) {}

    public function execute(AccountUser $user, string $pushToken): array
    {
        $updatedCount = $this->query->revoke($user, $pushToken);

        if ($updatedCount === 0) {
            throw new CustomException(ErrorCode::NOT_FOUND, '활성 디바이스 토큰을 찾을 수 없습니다.');
        }

        return [
            'revoked' => true,
        ];
    }
}
