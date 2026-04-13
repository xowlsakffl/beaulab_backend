<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Queries\Staff;

use App\Domains\Hospital\Models\Hospital;

/**
 * HospitalNameExistsForStaffQuery 역할 정의.
 * 병원 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class HospitalNameExistsForStaffQuery
{
    public function exists(string $name): bool
    {
        return Hospital::query()
            ->where('name', $name)
            ->exists();
    }
}
