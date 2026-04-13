<?php

namespace App\Domains\AccountStaff\Queries\Auth;

use App\Domains\AccountStaff\Models\AccountStaff;
use Illuminate\Support\Facades\DB;

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
