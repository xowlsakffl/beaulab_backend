<?php

namespace App\Domains\AccountHospital\Queries\Hospital;

use App\Domains\AccountHospital\Models\AccountHospital;

/**
 * 병원 계정 권한 정보 조회 Query.
 * Spatie role/permission 값을 API 응답용 배열로 변환한다.
 */
final class ProfileForAccountHospitalQuery
{
    /**
     * @return array{roles: list<string>, permissions: list<string>}
     */
    public function authorizationSnapshot(AccountHospital $hospital): array
    {
        return [
            'roles' => $hospital->getRoleNames()->values()->all(),
            'permissions' => $hospital->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
