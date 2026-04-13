<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Queries\Staff;

use App\Domains\HospitalBusinessRegistration\Models\HospitalBusinessRegistration;

/**
 * HospitalBusinessNumberExistsForStaffQuery 역할 정의.
 * 병원 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class HospitalBusinessNumberExistsForStaffQuery
{
    public function exists(string $businessNumber): bool
    {
        return HospitalBusinessRegistration::query()
            ->where('business_number', $businessNumber)
            ->exists();
    }
}
