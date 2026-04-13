<?php

namespace App\Domains\AccountHospital\Queries\Auth;

use App\Domains\AccountHospital\Models\AccountHospital;

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
