<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Queries\Staff;

use App\Domains\AccountUser\Models\AccountUser;

/**
 * AccountUserDeleteForStaffQuery 역할 정의.
 * 일반 회원 계정 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class AccountUserDeleteForStaffQuery
{
    public function softDelete(AccountUser $user): void
    {
        $user->delete();
    }
}
