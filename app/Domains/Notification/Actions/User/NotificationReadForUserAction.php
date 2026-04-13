<?php

namespace App\Domains\Notification\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Notification\Dto\NotificationInboxDto;
use App\Domains\Notification\Models\NotificationInbox;
use App\Domains\Notification\Queries\User\NotificationReadForUserQuery;

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
