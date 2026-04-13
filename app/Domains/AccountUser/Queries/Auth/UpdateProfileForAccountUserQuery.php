<?php

namespace App\Domains\AccountUser\Queries\Auth;

use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Support\Facades\DB;

final class UpdateProfileForAccountUserQuery
{
    public function update(AccountUser $user, array $filters): AccountUser
    {
        return DB::transaction(function () use ($user, $filters): AccountUser {
            $user->fill($filters)->save();

            return $user->fresh();
        });
    }
}
