<?php

namespace App\Domains\AccountUser\Queries\Auth;

use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Support\Facades\DB;

/**
 * 앱 사용자 프로필 저장 Query.
 * name/email 변경을 트랜잭션 안에서 저장하고 최신 모델을 반환한다.
 */
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
