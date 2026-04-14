<?php

namespace App\Domains\AccountUser\Queries\User\Auth;

use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Support\Facades\DB;

/**
 * 앱 사용자 비밀번호 저장 Query.
 * 비밀번호 변경 후 보안을 위해 기존 Sanctum 토큰을 모두 만료시킨다.
 */
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
