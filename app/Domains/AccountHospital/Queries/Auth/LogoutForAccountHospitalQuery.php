<?php

namespace App\Domains\AccountHospital\Queries\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * 병원 계정 로그아웃 토큰 Query.
 * 현재 access token만 삭제한다.
 */
final class LogoutForAccountHospitalQuery
{
    public function deleteCurrentToken(?Authenticatable $actor): void
    {
        $actor?->currentAccessToken()?->delete();
    }
}
