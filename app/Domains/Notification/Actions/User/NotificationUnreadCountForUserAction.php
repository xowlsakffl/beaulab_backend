<?php

namespace App\Domains\Notification\Actions\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Notification\Queries\User\NotificationUnreadForUserQuery;

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
