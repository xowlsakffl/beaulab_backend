<?php

namespace App\Domains\AccountStaff\Queries\Auth;

use App\Domains\AccountStaff\Models\AccountStaff;
use Illuminate\Support\Facades\DB;

/**
 * 스태프 프로필 저장 Query.
 * name/email 변경을 트랜잭션으로 저장하고 최신 모델을 반환한다.
 */
final class UpdateProfileForStaffQuery
{
    public function update(AccountStaff $staff, array $filters): AccountStaff
    {
        return DB::transaction(function () use ($staff, $filters): AccountStaff {
            $staff->fill($filters)->save();

            return $staff->fresh();
        });
    }
}
