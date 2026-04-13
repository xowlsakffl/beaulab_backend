<?php

namespace App\Domains\Notification\Actions\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Notification\Dto\NotificationPreferenceDto;
use App\Domains\Notification\Queries\User\NotificationPreferenceForUserQuery;

final class NotificationPreferenceUpdateForUserAction
{
    public function __construct(
        private readonly NotificationPreferenceForUserQuery $query,
    ) {}

    public function execute(AccountUser $user, array $payload): array
    {
        $values = [];

        foreach (['in_app', 'push', 'email'] as $channel) {
            if (array_key_exists($channel, $payload)) {
                $values[$channel] = (bool) $payload[$channel];
            }
        }

        if (array_key_exists('metadata', $payload)) {
            $values['metadata'] = $payload['metadata'];
        }

        $preference = $this->query->update($user, trim((string) $payload['event_type']), $values);

        return [
            'preference' => NotificationPreferenceDto::fromModel($preference),
        ];
    }
}
