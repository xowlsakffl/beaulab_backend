<?php

namespace App\Domains\AccountUser\Queries\Auth;

use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Support\Facades\DB;

final class UpdatePasswordForAccountUserQuery
{
    public function update(AccountUser $user, string $password): void
    {
        DB::transaction(function () use ($user, $password): void {
            $user->forceFill(['password' => $password])->save();
            $user->tokens()->delete();
        });
    }
}
