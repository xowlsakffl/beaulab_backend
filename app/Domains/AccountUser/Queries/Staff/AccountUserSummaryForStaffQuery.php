<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Queries\Staff;

use App\Domains\AccountUser\Models\AccountUser;

/**
 * AccountUserSummaryForStaffQuery 역할 정의.
 * 일반회원 목록 상단 집계 카드에 필요한 DB 조회를 캡슐화한다.
 */
final class AccountUserSummaryForStaffQuery
{
    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        $userCounts = AccountUser::query()
            ->withTrashed()
            ->selectRaw(<<<'SQL'
                SUM(CASE WHEN status = ? OR deleted_at IS NOT NULL THEN 1 ELSE 0 END) AS withdrawn_users,
                SUM(CASE WHEN deleted_at IS NULL AND status = ? THEN 1 ELSE 0 END) AS blocked_users,
                SUM(CASE WHEN deleted_at IS NULL AND status != ? AND warning_count > 0 THEN 1 ELSE 0 END) AS warned_users
                SQL, [
                AccountUser::STATUS_WITHDRAWN,
                AccountUser::STATUS_BLOCKED,
                AccountUser::STATUS_WITHDRAWN,
            ])
            ->first();

        return [
            'withdrawn_users' => (int) ($userCounts?->withdrawn_users ?? 0),
            'blocked_users' => (int) ($userCounts?->blocked_users ?? 0),
            'warned_users' => (int) ($userCounts?->warned_users ?? 0),
        ];
    }
}
