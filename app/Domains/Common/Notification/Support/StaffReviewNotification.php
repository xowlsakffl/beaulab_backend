<?php

namespace App\Domains\Common\Notification\Support;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Notification\Actions\StaffNotificationPublisher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class StaffReviewNotification
{
    public function __construct(private readonly StaffNotificationPublisher $publisher) {}

    public function changed(string $type, Model $target, ?string $before, string $after): void
    {
        if ($before === $after) {
            return;
        }
        $definition = app(StaffNotificationTypeRegistry::class)->definition($type);
        $this->publisher->invalidateTarget($definition['target_type'], (int) $target->id);
        if ($after !== 'PENDING') {
            return;
        }
        $actor = auth()->user();
        $this->publisher->record(
            eventType: $type,
            eventKey: $type.':'.$target->id.':'.($before === null ? 'created' : Str::uuid()),
            targetType: $definition['target_type'],
            targetId: (int) $target->id,
            payload: ['target_name' => (string) ($target->name ?? $target->hospital_name ?? '')],
            actorStaffId: $actor instanceof AccountStaff ? (int) $actor->id : null,
        );
    }
}
