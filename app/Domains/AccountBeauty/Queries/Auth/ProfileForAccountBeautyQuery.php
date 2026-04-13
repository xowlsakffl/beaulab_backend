<?php

namespace App\Domains\AccountBeauty\Queries\Auth;

use App\Domains\AccountBeauty\Models\AccountBeauty;

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
