<?php

namespace App\Domains\Notification\Queries\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Notification\Models\NotificationInbox;
use App\Domains\Notification\Models\NotificationPreference;
use Illuminate\Support\Collection;

final class NotificationPreferenceForUserQuery
{
    /**
     * @return Collection<string, NotificationPreference>
     */
    public function allByEventType(AccountUser $user): Collection
    {
        return NotificationPreference::query()
            ->where('owner_type', NotificationInbox::RECIPIENT_USER)
            ->where('owner_id', $user->id)
            ->get()
            ->keyBy('event_type');
    }

    public function update(AccountUser $user, string $eventType, array $values): NotificationPreference
    {
        return NotificationPreference::updateOrCreate(
            [
                'owner_type' => NotificationInbox::RECIPIENT_USER,
                'owner_id' => $user->id,
                'event_type' => $eventType,
            ],
            $values,
        );
    }
}
