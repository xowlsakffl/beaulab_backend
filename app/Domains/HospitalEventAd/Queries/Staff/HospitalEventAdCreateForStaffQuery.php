<?php

namespace App\Domains\HospitalEventAd\Queries\Staff;

use App\Domains\HospitalEventAd\Models\HospitalEventAd;

final class HospitalEventAdCreateForStaffQuery
{
    public function create(array $payload): HospitalEventAd
    {
        return HospitalEventAd::query()->create([
            'hospital_id' => (int) $payload['hospital_id'],
            'hospital_event_id' => (int) $payload['hospital_event_id'],
            'manager_staff_id' => $payload['manager_staff_id'] ?? null,
            'placement' => (string) $payload['placement'],
            'cost' => (int) ($payload['cost'] ?? 0),
            'start_at' => $payload['start_at'],
            'end_at' => $payload['end_at'],
        ]);
    }
}
