<?php

namespace App\Domains\AccountHospital\Queries\Auth;

use App\Domains\AccountHospital\Models\AccountHospital;
use Illuminate\Support\Facades\DB;

final class UpdatePasswordForAccountHospitalQuery
{
    public function update(AccountHospital $hospital, string $password): void
    {
        DB::transaction(function () use ($hospital, $password): void {
            $hospital->forceFill(['password' => $password])->save();
            $hospital->tokens()->delete();
        });
    }
}
