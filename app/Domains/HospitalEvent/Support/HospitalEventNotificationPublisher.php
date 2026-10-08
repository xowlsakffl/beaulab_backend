<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Support;

use App\Domains\Common\Notification\Actions\StaffNotificationPublisher;
use App\Domains\HospitalEvent\Models\HospitalEvent;

final class HospitalEventNotificationPublisher
{
    public function __construct(private readonly StaffNotificationPublisher $publisher) {}

    public function created(HospitalEvent $event, ?int $actorStaffId = null): void
    {
        if ($event->allow_status === HospitalEvent::ALLOW_PENDING) {
            $this->requested($event, 'hospital-event:requested:created:'.$event->id, $actorStaffId);
        }
    }

    public function allowStatusChanged(
        HospitalEvent $event,
        string $beforeStatus,
        string $afterStatus,
        int $historyId,
        ?int $actorStaffId = null,
    ): void {
        if ($beforeStatus === $afterStatus) {
            return;
        }

        $this->publisher->invalidateTarget('hospital_event', (int) $event->id);
        if ($afterStatus === HospitalEvent::ALLOW_PENDING) {
            $this->requested($event, 'hospital-event:requested:history:'.$historyId, $actorStaffId);
        }
    }

    private function requested(HospitalEvent $event, string $eventKey, ?int $actorStaffId): void
    {
        $this->publisher->record(
            eventType: 'staff.hospital_event.requested',
            eventKey: $eventKey,
            targetType: 'hospital_event',
            targetId: (int) $event->id,
            payload: array_filter([
                'hospital_id' => (int) $event->hospital_id,
                'hospital_name' => $event->hospital?->name,
                'target_name' => $event->name,
            ], static fn (mixed $value): bool => $value !== null),
            actorStaffId: $actorStaffId,
        );
    }
}
