<?php

namespace App\Domains\Common\Notification\Actions\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Notification\Dto\NotificationPreferenceDto;
use App\Domains\Common\Notification\Models\NotificationPreference;
use App\Domains\Common\Notification\Queries\User\NotificationPreferenceForUserQuery;

/**
 * 이벤트별 알림 설정 목록 조회 유스케이스.
 * 저장된 설정이 없어도 기본 이벤트 타입은 기본값으로 내려준다.
 */
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
