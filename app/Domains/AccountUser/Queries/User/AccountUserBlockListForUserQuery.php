<?php

namespace App\Domains\AccountUser\Queries\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Models\AccountUserBlock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 앱 사용자 차단 목록 조회 쿼리.
 * 차단 목록 페이지네이션과 연관 사용자 로딩을 담당한다.
 */
final class AccountUserBlockListForUserQuery
{
    public function paginate(AccountUser $user, array $filters): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 50));

        return AccountUserBlock::query()
            ->where('blocker_user_id', $user->id)
            ->with('blocked:id,nickname,email,status')
            ->orderByDesc('blocked_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
