<?php

namespace App\Domains\Notification\Actions\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Notification\Queries\User\NotificationReadForUserQuery;

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
