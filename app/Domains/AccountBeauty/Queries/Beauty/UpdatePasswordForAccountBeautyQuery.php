<?php

namespace App\Domains\AccountBeauty\Queries\Beauty;

use App\Domains\AccountBeauty\Models\AccountBeauty;
use Illuminate\Support\Facades\DB;

/**
 * 뷰티 계정 비밀번호 저장 Query.
 * 비밀번호 변경 후 기존 Sanctum 토큰을 모두 만료시킨다.
 */
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
