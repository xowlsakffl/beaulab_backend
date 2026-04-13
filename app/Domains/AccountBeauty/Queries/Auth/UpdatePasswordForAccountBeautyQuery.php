<?php

namespace App\Domains\AccountBeauty\Queries\Auth;

use App\Domains\AccountBeauty\Models\AccountBeauty;
use Illuminate\Support\Facades\DB;

final class UpdatePasswordForAccountBeautyQuery
{
    public function update(AccountBeauty $beauty, string $password): void
    {
        DB::transaction(function () use ($beauty, $password): void {
            $beauty->forceFill(['password' => $password])->save();
            $beauty->tokens()->delete();
        });
    }
}
