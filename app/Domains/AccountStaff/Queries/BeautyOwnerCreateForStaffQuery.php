<?php

namespace App\Domains\AccountStaff\Queries;

use App\Domains\AccountBeauty\Models\AccountBeauty;

/**
 * BeautyOwnerCreateForStaffQuery 역할 정의.
 * 스태프 계정 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class BeautyOwnerCreateForStaffQuery
{
    public function create(array $data): AccountBeauty
    {
        return AccountBeauty::create($data);
    }
}
