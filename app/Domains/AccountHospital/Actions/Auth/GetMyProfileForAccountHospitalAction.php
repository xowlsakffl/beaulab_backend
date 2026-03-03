<?php

namespace App\Domains\AccountHospital\Actions\Auth;

use App\Domains\AccountHospital\Dto\Auth\ProfileForAccountHospitalDto;
use App\Domains\AccountHospital\Models\AccountHospital;

final class GetMyProfileForAccountHospitalAction
{
    /**
     * @return array{profile: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(AccountHospital $hospital): array
    {
        return [
            'profile' => ProfileForAccountHospitalDto::fromModel($hospital)->toArray(),
            'roles' => $hospital->getRoleNames()->values()->all(),
            'permissions' => $hospital->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
