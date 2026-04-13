<?php

namespace App\Domains\AccountUser\Queries\Auth;

use App\Domains\AccountUser\Models\AccountUser;

final class ProfileForAccountUserQuery
{
    /**
     * @return array{roles: list<string>, permissions: list<string>}
     */
    public function authorizationSnapshot(AccountUser $user): array
    {
        return [
            'roles' => $user->getRoleNames()->values()->all(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
