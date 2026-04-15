<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Queries\Staff;

use App\Domains\AccountUser\Models\AccountUser;

/**
 * AccountUserUpdateForStaffQuery 역할 정의.
 * 일반 회원 계정 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class AccountUserUpdateForStaffQuery
{
    public function update(AccountUser $user, array $payload): AccountUser
    {
        $filter = [];
        foreach (['name', 'nickname', 'status'] as $field) {
            if (array_key_exists($field, $payload)) {
                $filter[$field] = $payload[$field];
            }
        }

        if ($filter !== []) {
            $user->update($filter);
        }

        return $user;
    }
}
