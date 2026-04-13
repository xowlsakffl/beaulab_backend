<?php

namespace App\Domains\AccountHospital\Queries\Auth;

use App\Domains\AccountHospital\Models\AccountHospital;
use Illuminate\Support\Facades\DB;

final class UpdateProfileForAccountHospitalQuery
{
    public function update(AccountHospital $hospital, array $filters): AccountHospital
    {
        return DB::transaction(function () use ($hospital, $filters): AccountHospital {
            $hospital->fill($filters)->save();

            return $hospital->fresh();
        });
    }
}
