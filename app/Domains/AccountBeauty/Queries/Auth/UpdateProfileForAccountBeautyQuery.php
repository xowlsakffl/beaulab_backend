<?php

namespace App\Domains\AccountBeauty\Queries\Auth;

use App\Domains\AccountBeauty\Models\AccountBeauty;
use Illuminate\Support\Facades\DB;

/**
 * 뷰티 계정 프로필 저장 Query.
 * name/email 변경을 트랜잭션으로 저장하고 최신 모델을 반환한다.
 */
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
