<?php

namespace App\Domains\Talk\Queries\Staff;

use App\Domains\Talk\Models\Talk;

/**
 * TalkDeleteForStaffQuery 역할 정의.
 * 토크 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class TalkDeleteForStaffQuery
{
    public function softDelete(Talk $talk): void
    {
        $talk->comments()->delete();
        $talk->delete();
    }
}
