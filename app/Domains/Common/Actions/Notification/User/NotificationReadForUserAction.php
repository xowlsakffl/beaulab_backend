<?php

namespace App\Domains\Common\Actions\Notification\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Dto\Notification\NotificationInboxDto;
use App\Domains\Common\Models\Notification\NotificationInbox;
use App\Domains\Common\Queries\Notification\User\NotificationReadForUserQuery;

/**
 * 알림 단건 읽음 처리 유스케이스.
 * 본인 알림인지 확인한 뒤 unread 집계 버킷을 닫는다.
 */
final class NotificationReadForUserAction
{
    public function __construct(
        private readonly NotificationReadForUserQuery $query,
    ) {}

    public function execute(NotificationInbox $notification, AccountUser $user): array
    {
        $this->assertOwner($notification, (int) $user->id);
        $notification = $this->query->read($notification);

        return [
            'notification' => NotificationInboxDto::fromModel($notification),
        ];
    }

    private function assertOwner(NotificationInbox $notification, int $userId): void
    {
        if (
            $notification->recipient_type !== NotificationInbox::RECIPIENT_USER
            || (int) $notification->recipient_id !== $userId
        ) {
            throw new CustomException(ErrorCode::FORBIDDEN, '본인 알림만 읽음 처리할 수 있습니다.');
        }
    }
}
