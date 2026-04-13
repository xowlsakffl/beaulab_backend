<?php

namespace App\Domains\AccountStaff\Queries\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * 스태프 로그아웃 토큰 Query.
 * 현재 access token만 삭제한다.
 */
final class LogoutForStaffQuery
{
    public function deleteCurrentToken(?Authenticatable $actor): void
    {
        $actor?->currentAccessToken()?->delete();
    }
}
