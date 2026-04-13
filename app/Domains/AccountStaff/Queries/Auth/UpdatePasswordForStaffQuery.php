<?php

namespace App\Domains\AccountStaff\Queries\Auth;

use App\Domains\AccountStaff\Models\AccountStaff;
use Illuminate\Support\Facades\DB;

/**
 * 스태프 비밀번호 저장 Query.
 * 비밀번호 변경 후 기존 Sanctum 토큰을 모두 만료시킨다.
 */
final class UpdatePasswordForStaffQuery
{
    public function update(AccountStaff $staff, string $password): void
    {
        DB::transaction(function () use ($staff, $password): void {
            $staff->forceFill(['password' => $password])->save();
            $staff->tokens()->delete();
        });
    }
}
