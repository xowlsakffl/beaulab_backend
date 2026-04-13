<?php

namespace App\Domains\AccountStaff\Queries\Auth;

use App\Domains\AccountStaff\Models\AccountStaff;

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
