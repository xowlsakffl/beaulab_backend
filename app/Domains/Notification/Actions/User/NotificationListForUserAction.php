<?php

namespace App\Domains\Notification\Actions\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Notification\Dto\NotificationInboxDto;
use App\Domains\Notification\Queries\User\NotificationListForUserQuery;

final class NotificationListForUserAction
{
    public function __construct(
        private readonly NotificationListForUserQuery $query,
    ) {}

    public function execute(AccountUser $user, array $filters): array
    {
        $paginator = $this->query->paginate((int) $user->id, $filters);

        return [
            'items' => collect($paginator->items())
                ->map(fn ($notification) => NotificationInboxDto::fromModel($notification))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }
}
