<?php

namespace App\Domains\AccountStaff\Queries\Auth;

use App\Domains\AccountStaff\Models\AccountStaff;
use Illuminate\Support\Facades\DB;

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
