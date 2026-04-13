<?php

namespace App\Domains\AccountBeauty\Queries\Auth;

use App\Domains\AccountBeauty\Models\AccountBeauty;
use Illuminate\Support\Facades\DB;

final class UpdateProfileForAccountBeautyQuery
{
    public function update(AccountBeauty $beauty, array $filters): AccountBeauty
    {
        return DB::transaction(function () use ($beauty, $filters): AccountBeauty {
            $beauty->fill($filters)->save();

            return $beauty->fresh();
        });
    }
}
