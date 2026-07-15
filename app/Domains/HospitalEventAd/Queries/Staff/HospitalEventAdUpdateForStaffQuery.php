<?php

namespace App\Domains\HospitalEventAd\Queries\Staff;

use App\Domains\HospitalEventAd\Models\HospitalEventAd;

final class HospitalEventAdUpdateForStaffQuery
{
    public function update(HospitalEventAd $ad, array $payload): HospitalEventAd
    {
        $ad->fill(collect($payload)
            ->only([
                'hospital_event_id',
                'manager_staff_id',
                'placement',
                'cost',
                'start_at',
                'end_at',
            ])
            ->all());

        $ad->save();

        return $ad;
    }
}
