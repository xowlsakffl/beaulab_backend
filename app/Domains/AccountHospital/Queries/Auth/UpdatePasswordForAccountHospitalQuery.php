<?php

namespace App\Domains\AccountHospital\Queries\Auth;

use App\Domains\AccountHospital\Models\AccountHospital;
use Illuminate\Support\Facades\DB;

/**
 * 병원 계정 비밀번호 저장 Query.
 * 비밀번호 변경 후 기존 Sanctum 토큰을 모두 만료시킨다.
 */
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
