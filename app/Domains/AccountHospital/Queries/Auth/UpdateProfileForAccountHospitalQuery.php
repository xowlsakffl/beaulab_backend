<?php

namespace App\Domains\AccountHospital\Queries\Auth;

use App\Domains\AccountHospital\Models\AccountHospital;
use Illuminate\Support\Facades\DB;

/**
 * 병원 계정 프로필 저장 Query.
 * name/email 변경을 트랜잭션으로 저장하고 최신 모델을 반환한다.
 */
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
