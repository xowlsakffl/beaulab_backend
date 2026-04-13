<?php

namespace App\Domains\HospitalBusinessRegistration\Queries;

use App\Domains\HospitalBusinessRegistration\Models\HospitalBusinessRegistration;

/**
 * HospitalBusinessRegistrationCreateForStaffQuery 역할 정의.
 * 병원 사업자 등록 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class HospitalBusinessRegistrationCreateForStaffQuery
{
    public function create(array $data): HospitalBusinessRegistration
    {
        return HospitalBusinessRegistration::create($data);
    }
}
