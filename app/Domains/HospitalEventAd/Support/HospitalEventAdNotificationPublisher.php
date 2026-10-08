<?php

declare(strict_types=1);

namespace App\Domains\HospitalEventAd\Support;

use App\Domains\Common\Notification\Actions\StaffNotificationPublisher;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;

final class HospitalEventAdNotificationPublisher
{
    public function __construct(private readonly StaffNotificationPublisher $publisher) {}

    public function created(HospitalEventAd $ad, ?int $actorStaffId = null): void
    {
        if ($ad->allow_status === HospitalEventAd::ALLOW_PENDING) {
            $this->requested($ad, 'hospital-event-ad:requested:created:'.$ad->id, $actorStaffId);
        }
    }

    public function allowStatusChanged(
        HospitalEventAd $ad,
        string $beforeStatus,
        string $afterStatus,
        int $historyId,
        ?int $actorStaffId = null,
    ): void {
        if ($beforeStatus === $afterStatus) {
            return;
        }

        $this->publisher->invalidateTarget('hospital_event_ad', (int) $ad->id);
        if ($afterStatus === HospitalEventAd::ALLOW_PENDING) {
            $this->requested($ad, 'hospital-event-ad:requested:history:'.$historyId, $actorStaffId);
        }
    }

    private function requested(HospitalEventAd $ad, string $eventKey, ?int $actorStaffId): void
    {
        $this->publisher->record(
            eventType: 'staff.hospital_event_ad.requested',
            eventKey: $eventKey,
            targetType: 'hospital_event_ad',
            targetId: (int) $ad->id,
            payload: array_filter([
                'hospital_id' => (int) $ad->hospital_id,
                'hospital_name' => $ad->hospital?->name,
                'target_name' => $ad->hospitalEvent?->name,
            ], static fn (mixed $value): bool => $value !== null),
            actorStaffId: $actorStaffId,
        );
    }
}
