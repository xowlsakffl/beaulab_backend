<?php

namespace App\Domains\Notification\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Notification\Queries\User\NotificationDeviceForUserQuery;

/**
 * 앱/웹 푸시 디바이스 토큰 폐기 유스케이스.
 * 활성 토큰이 없으면 잘못된 폐기 요청으로 보고 NOT_FOUND를 반환한다.
 */
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
