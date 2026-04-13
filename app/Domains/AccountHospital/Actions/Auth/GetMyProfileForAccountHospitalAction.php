<?php

namespace App\Domains\AccountHospital\Actions\Auth;

use App\Domains\AccountHospital\Dto\Auth\ProfileForAccountHospitalDto;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountHospital\Queries\Auth\ProfileForAccountHospitalQuery;

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
