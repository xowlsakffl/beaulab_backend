<?php

namespace App\Domains\AccountUser\Queries\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * 앱 사용자 로그아웃 토큰 Query.
 * 현재 요청에 사용된 access token만 삭제한다.
 */
final class LogoutForAccountUserQuery
{
    public function deleteCurrentToken(?Authenticatable $actor): void
    {
        $actor?->currentAccessToken()?->delete();
    }
}
