<?php

namespace App\Domains\Hospital\Actions\Auth;

use App\Domains\Hospital\Dto\Auth\ProfileForHospitalDto;
use App\Domains\Hospital\Models\AccountHospital;

final class GetMyProfileForHospitalAction
{
    /**
     * @return array{profile: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(AccountHospital $hospital): array
    {
        return [
            'profile' => ProfileForHospitalDto::fromModel($hospital)->toArray(),
            'roles' => $hospital->getRoleNames()->values()->all(),
            'permissions' => $hospital->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
