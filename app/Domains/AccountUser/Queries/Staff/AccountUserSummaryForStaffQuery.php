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

        $totalUsers = AccountUser::query()
            ->where('status', '!=', AccountUser::STATUS_WITHDRAWN)
            ->count();
        $withdrawnUsers = AccountUser::query()
            ->withTrashed()
            ->where(function ($query) {
                $query->where('status', AccountUser::STATUS_WITHDRAWN)
                    ->orWhereNotNull('deleted_at');
            })
            ->count();

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
            'daily_visitors' => AccountUserAccessLog::query()
                ->where('accessed_at', '>=', $todayStart)
                ->distinct()
                ->count('account_user_id'),
            'monthly_visitors' => AccountUserAccessLog::query()
                ->where('accessed_at', '>=', $last30DaysStart)
                ->distinct()
                ->count('account_user_id'),
            'total_users' => $totalUsers,
            'withdrawn_users' => $withdrawnUsers,
            'blocked_users' => AccountUser::query()
                ->where('status', AccountUser::STATUS_BLOCKED)
                ->count(),
            'warned_users' => AccountUser::query()
                ->where('status', '!=', AccountUser::STATUS_WITHDRAWN)
                ->where('warning_count', '>', 0)
                ->count(),
            'signup_channels' => $signupChannels,
        ];
    }
}
