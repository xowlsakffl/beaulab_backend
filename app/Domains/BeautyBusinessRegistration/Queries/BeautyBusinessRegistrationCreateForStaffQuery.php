<?php

namespace App\Domains\BeautyBusinessRegistration\Queries;

use App\Domains\BeautyBusinessRegistration\Models\BeautyBusinessRegistration;

/**
 * BeautyBusinessRegistrationCreateForStaffQuery 역할 정의.
 * 뷰티 사업자 등록 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class BeautyBusinessRegistrationCreateForStaffQuery
{
    public function create(array $data): BeautyBusinessRegistration
    {
        return BeautyBusinessRegistration::create($data);
    }
}
