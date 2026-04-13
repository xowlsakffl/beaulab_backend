<?php

namespace App\Domains\AccountBeauty\Queries\Auth;

use App\Domains\AccountBeauty\Models\AccountBeauty;

/**
 * 뷰티 계정 권한 정보 조회 Query.
 * Spatie role/permission 값을 API 응답용 배열로 변환한다.
 */
final class ProfileForAccountBeautyQuery
{
    /**
     * @return array{roles: list<string>, permissions: list<string>}
     */
    public function authorizationSnapshot(AccountBeauty $beauty): array
    {
        return [
            'roles' => $beauty->getRoleNames()->values()->all(),
            'permissions' => $beauty->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
