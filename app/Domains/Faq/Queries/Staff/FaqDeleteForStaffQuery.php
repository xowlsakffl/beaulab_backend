<?php

namespace App\Domains\Faq\Queries\Staff;

use App\Domains\Faq\Models\Faq;

/**
 * FaqDeleteForStaffQuery 역할 정의.
 * FAQ 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class FaqDeleteForStaffQuery
{
    public function delete(Faq $faq): void
    {
        $faq->delete();
    }
}
