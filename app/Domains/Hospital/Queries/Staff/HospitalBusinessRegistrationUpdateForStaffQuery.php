<?php

namespace App\Domains\Hospital\Queries\Staff;

use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Models\HospitalBusinessRegistration;

/**
 * HospitalBusinessRegistrationUpdateForStaffQuery 역할 정의.
 * 병원 사업자 등록 수정에 필요한 조회/저장을 캡슐화한다.
 */
final class HospitalBusinessRegistrationUpdateForStaffQuery
{
    public function findForHospital(Hospital $hospital): ?HospitalBusinessRegistration
    {
        $businessRegistration = $hospital->businessRegistration()->first();

        return $businessRegistration instanceof HospitalBusinessRegistration
            ? $businessRegistration
            : null;
    }

    public function update(HospitalBusinessRegistration $businessRegistration, array $updates): HospitalBusinessRegistration
    {
        $businessRegistration->fill($updates);

        if ($businessRegistration->isDirty()) {
            $businessRegistration->save();
        }

        return $businessRegistration->fresh();
    }
}
