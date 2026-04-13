<?php

namespace App\Domains\AccountStaff\Queries\Auth;

use App\Domains\AccountStaff\Models\AccountStaff;

/**
 * 스태프 권한 정보 조회 Query.
 * Spatie role/permission 값을 API 응답용 배열로 변환한다.
 */
final class ProfileForStaffQuery
{
    /**
     * @return array{roles: list<string>, permissions: list<string>}
     */
    public function authorizationSnapshot(AccountStaff $staff): array
    {
        return [
            'roles' => $staff->getRoleNames()->values()->all(),
            'permissions' => $staff->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
