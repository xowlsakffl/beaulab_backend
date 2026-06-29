<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Queries\Staff;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Models\AccountUserAccessLog;
use Illuminate\Support\Carbon;

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
        $todayStart = Carbon::today();
        $last30DaysStart = Carbon::now()->subDays(30);

        $userCounts = AccountUser::query()
            ->withTrashed()
            ->selectRaw(<<<'SQL'
                SUM(CASE WHEN deleted_at IS NULL AND status != ? THEN 1 ELSE 0 END) AS total_users,
                SUM(CASE WHEN status = ? OR deleted_at IS NOT NULL THEN 1 ELSE 0 END) AS withdrawn_users,
                SUM(CASE WHEN deleted_at IS NULL AND status = ? THEN 1 ELSE 0 END) AS blocked_users,
                SUM(CASE WHEN deleted_at IS NULL AND status != ? AND warning_count > 0 THEN 1 ELSE 0 END) AS warned_users
                SQL, [
                AccountUser::STATUS_WITHDRAWN,
                AccountUser::STATUS_WITHDRAWN,
                AccountUser::STATUS_BLOCKED,
                AccountUser::STATUS_WITHDRAWN,
            ])
            ->first();

        $visitorCounts = AccountUserAccessLog::query()
            ->where('accessed_at', '>=', $last30DaysStart)
            ->selectRaw('COUNT(DISTINCT CASE WHEN accessed_at >= ? THEN account_user_id END) AS daily_visitors', [$todayStart])
            ->selectRaw('COUNT(DISTINCT account_user_id) AS monthly_visitors')
            ->first();

        $signupCounts = AccountUser::query()
            ->where('status', '!=', AccountUser::STATUS_WITHDRAWN)
            ->selectRaw('signup_channel, COUNT(*) as aggregate_count')
            ->groupBy('signup_channel')
            ->pluck('aggregate_count', 'signup_channel');

        $signupChannelLabels = AccountUser::signupChannelLabels();
        $signupChannels = [];

        foreach ($signupChannelLabels as $channel => $label) {
            $signupChannels[] = [
                'channel' => $channel,
                'label' => $label,
                'count' => (int) ($signupCounts[$channel] ?? 0),
            ];
        }

        return [
            'daily_visitors' => (int) ($visitorCounts?->daily_visitors ?? 0),
            'monthly_visitors' => (int) ($visitorCounts?->monthly_visitors ?? 0),
            'total_users' => (int) ($userCounts?->total_users ?? 0),
            'withdrawn_users' => (int) ($userCounts?->withdrawn_users ?? 0),
            'blocked_users' => (int) ($userCounts?->blocked_users ?? 0),
            'warned_users' => (int) ($userCounts?->warned_users ?? 0),
            'signup_channels' => $signupChannels,
        ];
    }
}
