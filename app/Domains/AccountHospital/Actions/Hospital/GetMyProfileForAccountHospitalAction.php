<?php

namespace App\Domains\AccountHospital\Actions\Hospital;

use App\Domains\AccountHospital\Dto\Hospital\ProfileForAccountHospitalDto;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountHospital\Queries\Hospital\ProfileForAccountHospitalQuery;

/**
 * 병원 계정 내 프로필 조회 유스케이스.
 * 프로필과 현재 role/permission 스냅샷을 함께 반환한다.
 */
final class GetMyProfileForAccountHospitalAction
{
    public function __construct(
        private readonly ProfileForAccountHospitalQuery $query,
    ) {}

    /**
     * @return array{profile: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(AccountHospital $hospital): array
    {
        $authorization = $this->query->authorizationSnapshot($hospital);

        return [
            'profile' => ProfileForAccountHospitalDto::fromModel($hospital)->toArray(),
            'roles' => $authorization['roles'],
            'permissions' => $authorization['permissions'],
        ];
    }
}
