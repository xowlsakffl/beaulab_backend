<?php

namespace App\Domains\AccountUser\Queries\Auth;

use App\Domains\AccountUser\Models\AccountUser;

/**
 * 앱 사용자 권한 정보 조회 Query.
 * Spatie role/permission 값을 API 응답에 필요한 단순 배열로 정리한다.
 */
final class ProfileForAccountUserQuery
{
    /**
     * @return array{roles: list<string>, permissions: list<string>}
     */
    public function authorizationSnapshot(AccountUser $user): array
    {
        return [
            'roles' => $user->getRoleNames()->values()->all(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
