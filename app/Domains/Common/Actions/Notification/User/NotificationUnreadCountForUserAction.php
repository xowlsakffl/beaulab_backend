<?php

namespace App\Domains\Common\Actions\Notification\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Queries\Notification\User\NotificationUnreadForUserQuery;

/**
 * 앱 사용자 안읽음 알림 카운트 조회 유스케이스.
 * 알림 row 수와 집계된 이벤트 수를 분리해서 반환한다.
 */
final class NotificationUnreadCountForUserAction
{
    public function __construct(
        private readonly NotificationUnreadForUserQuery $query,
    ) {}

    public function execute(AccountUser $user): array
    {
        return $this->query->counts($user);
    }
}
