<?php

namespace App\Domains\Notification\Actions\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Notification\Dto\NotificationPreferenceDto;
use App\Domains\Notification\Queries\User\NotificationPreferenceForUserQuery;

/**
 * 이벤트별 알림 채널 설정 변경 유스케이스.
 * in_app, push, email 중 요청에 포함된 값만 변경한다.
 */
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
