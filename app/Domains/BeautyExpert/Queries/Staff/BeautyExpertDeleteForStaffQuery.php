<?php

namespace App\Domains\BeautyExpert\Queries\Staff;

use App\Domains\BeautyExpert\Models\BeautyExpert;

/**
 * BeautyExpertDeleteForStaffQuery 역할 정의.
 * 뷰티 전문가 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class BeautyExpertDeleteForStaffQuery
{
    public function softDelete(BeautyExpert $expert): void
    {
        $expert->delete();
    }
}
