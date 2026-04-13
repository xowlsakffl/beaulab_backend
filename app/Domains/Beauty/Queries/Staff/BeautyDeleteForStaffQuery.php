<?php

namespace App\Domains\Beauty\Queries\Staff;

use App\Domains\Beauty\Models\Beauty;

/**
 * BeautyDeleteForStaffQuery 역할 정의.
 * 뷰티 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class BeautyDeleteForStaffQuery
{
    /**
     * 병원 삭제 soft delete
     */
    public function softDelete(Beauty $beauty): void
    {
        $beauty->delete();
    }
}
