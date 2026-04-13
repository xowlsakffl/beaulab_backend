<?php

namespace App\Domains\Notification\Actions\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Notification\Dto\NotificationPreferenceDto;
use App\Domains\Notification\Models\NotificationPreference;
use App\Domains\Notification\Queries\User\NotificationPreferenceForUserQuery;

final class NotificationPreferenceListForUserAction
{
    public function __construct(
        private readonly NotificationPreferenceForUserQuery $query,
    ) {}

    public function execute(AccountUser $user): array
    {
        $preferences = $this->query->allByEventType($user);

        $eventTypes = collect(NotificationPreference::DEFAULT_EVENT_TYPES)
            ->merge($preferences->keys())
            ->unique()
            ->values();

        return [
            'items' => $eventTypes
                ->map(function (string $eventType) use ($preferences): array {
                    $preference = $preferences->get($eventType);

                    return $preference instanceof NotificationPreference
                        ? NotificationPreferenceDto::fromModel($preference)
                        : NotificationPreferenceDto::default($eventType);
                })
                ->all(),
        ];
    }
}
