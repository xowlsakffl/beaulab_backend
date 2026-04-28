<?php

namespace App\Domains\Common\Notification\Queries\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Notification\Models\NotificationInbox;
use App\Domains\Common\Notification\Models\NotificationPreference;
use Illuminate\Support\Collection;

/**
 * 이벤트별 알림 설정 Query.
 * owner_type/owner_id/event_type 조합을 기준으로 사용자별 설정을 관리한다.
 */
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
