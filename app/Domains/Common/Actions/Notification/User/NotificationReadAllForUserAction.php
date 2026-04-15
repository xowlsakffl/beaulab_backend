<?php

namespace App\Domains\Common\Actions\Notification\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Queries\Notification\User\NotificationReadForUserQuery;

/**
 * 내 알림 전체 읽음 처리 유스케이스.
 * 모든 unread 알림의 open_aggregation_key를 비워 다음 이벤트가 새 묶음으로 생성되게 한다.
 */
final class NotificationReadAllForUserAction
{
    public function __construct(
        private readonly NotificationReadForUserQuery $query,
    ) {}

    public function execute(AccountUser $user): array
    {
        return [
            'read_count' => $this->query->readAll($user),
        ];
    }
}
