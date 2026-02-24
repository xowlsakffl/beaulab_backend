<?php

declare(strict_types=1);

namespace App\Domains\User\Queries\Staff;

use App\Domains\User\Models\AccountUser;

final class AccountUserUpdateForStaffQuery
{
    public function update(AccountUser $user, array $payload): AccountUser
    {
        $filter = [];
        foreach (['name', 'email', 'status'] as $field) {
            if (array_key_exists($field, $payload)) {
                $filter[$field] = $payload[$field];
            }
        }

        if ($filter !== []) {
            $user->update($filter);
        }

        return $user;
    }
}
