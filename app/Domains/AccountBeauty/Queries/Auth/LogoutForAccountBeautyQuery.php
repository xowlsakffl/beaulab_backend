<?php

namespace App\Domains\AccountBeauty\Queries\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * 뷰티 계정 로그아웃 토큰 Query.
 * 현재 access token만 삭제한다.
 */
final class LogoutForAccountBeautyQuery
{
    public function deleteCurrentToken(?Authenticatable $actor): void
    {
        $actor?->currentAccessToken()?->delete();
    }
}
