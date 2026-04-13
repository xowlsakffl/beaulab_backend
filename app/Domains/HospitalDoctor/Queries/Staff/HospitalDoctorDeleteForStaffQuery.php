<?php

namespace App\Domains\HospitalDoctor\Queries\Staff;

use App\Domains\HospitalDoctor\Models\HospitalDoctor;

/**
 * HospitalDoctorDeleteForStaffQuery 역할 정의.
 * 병원 의사 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class HospitalDoctorDeleteForStaffQuery
{
    public function softDelete(HospitalDoctor $doctor): void
    {
        $doctor->delete();
    }
}
