<?php

namespace App\Domains\Notice\Queries\Staff;

use App\Domains\Notice\Models\Notice;

/**
 * NoticeDeleteForStaffQuery 역할 정의.
 * 공지사항 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class NoticeDeleteForStaffQuery
{
    public function delete(Notice $notice): void
    {
        $notice->delete();
    }
}
