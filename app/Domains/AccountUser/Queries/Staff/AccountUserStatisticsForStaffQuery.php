<?php

namespace App\Domains\AccountUser\Queries\Staff;

use App\Domains\AccountUser\Models\AccountUser;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class AccountUserStatisticsForStaffQuery
{
    private function members(): Builder
    {
        return AccountUser::query()->whereIn('status', [AccountUser::STATUS_ACTIVE, AccountUser::STATUS_SUSPENDED]);
    }

    private function databaseTime(CarbonImmutable $time): string
    {
        return $time->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
    }

    public static function percentage(int $current, int $previous): ?float
    {
        return $previous === 0 ? ($current === 0 ? 0.0 : null) : round(($current - $previous) / $previous * 100, 1);
    }

    public function summary(): array
    {
        $now = CarbonImmutable::now('Asia/Seoul');
        $today = $now->startOfDay();
        $yesterday = $now->subDay();
        $previousMonth = $now->subMonthNoOverflow();
        $signupCount = fn ($start, $end): int => AccountUser::withTrashed()
            ->whereBetween('created_at', [$this->databaseTime($start), $this->databaseTime($end)])->count();
        $visitCount = fn ($start, $end): int => DB::table('site_visit_sessions')
            ->whereBetween('started_at', [$this->databaseTime($start), $this->databaseTime($end)])->count();
        $new = $signupCount($today, $now);
        $priorNew = $signupCount($yesterday->startOfDay(), $yesterday);
        $todayVisits = $visitCount($today, $now);
        $monthVisits = $visitCount($now->startOfMonth(), $now);
        $hasVisits = DB::table('site_visit_sessions')->where('started_at', '<=', $this->databaseTime($now))->exists();

        return [
            'as_of' => $now->toIso8601String(),
            'has_members' => AccountUser::withTrashed()->where('created_at', '<=', $this->databaseTime($now))->exists(),
            'has_visits' => $hasVisits,
            'today_visits' => ['value' => $hasVisits ? $todayVisits : null, 'change_percent' => $hasVisits ? self::percentage($todayVisits, $visitCount($yesterday->startOfDay(), $yesterday)) : null],
            'month_visits' => ['value' => $hasVisits ? $monthVisits : null, 'change_percent' => $hasVisits ? self::percentage($monthVisits, $visitCount($previousMonth->startOfMonth(), $previousMonth)) : null],
            'total_members' => ['value' => $this->members()->where('created_at', '<=', $this->databaseTime($now))->count(), 'increase' => $new],
            'today_signups' => ['value' => $new, 'change_percent' => self::percentage($new, $priorNew)],
        ];
    }

    public function channels(): array
    {
        $counts = $this->members()->where('created_at', '<=', now())->selectRaw('signup_channel, COUNT(*) AS aggregate')
            ->groupBy('signup_channel')->pluck('aggregate', 'signup_channel');
        $labels = AccountUser::signupChannelLabels();
        $normalized = [];
        foreach ($counts as $code => $count) {
            $key = isset($labels[$code]) ? $code : AccountUser::SIGNUP_CHANNEL_UNKNOWN;
            $normalized[$key] = ($normalized[$key] ?? 0) + (int) $count;
        }
        $total = array_sum($normalized);
        $rows = [];
        foreach ($normalized as $code => $count) {
            $rows[] = ['channel' => $code, 'label' => $labels[$code], 'count' => $count, 'percentage' => $total > 0 ? round($count / $total * 100, 1) : 0.0];
        }
        usort($rows, fn ($a, $b) => ($b['count'] <=> $a['count']) ?: strcmp($a['channel'], $b['channel']));

        return ['total' => $total, 'items' => $rows];
    }

    public function visits(string $period): array
    {
        $now = CarbonImmutable::now('Asia/Seoul');
        $monthly = $period === 'monthly';
        $start = $monthly ? $now->startOfMonth()->subMonths(11) : $now->startOfDay()->subDays(29);
        $format = $monthly ? '%Y-%m' : '%Y-%m-%d';
        $offset = CarbonImmutable::now(config('app.timezone'))->format('P');
        $rows = DB::table('site_visit_sessions')
            ->whereBetween('started_at', [$this->databaseTime($start), $this->databaseTime($now)])
            ->selectRaw("DATE_FORMAT(CONVERT_TZ(started_at, ?, '+09:00'), ?) AS bucket, COUNT(*) AS aggregate", [$offset, $format])
            ->groupBy('bucket')->pluck('aggregate', 'bucket');
        $points = [];
        for ($date = $start; $date <= $now; $date = $monthly ? $date->addMonth() : $date->addDay()) {
            $key = $date->format($monthly ? 'Y-m' : 'Y-m-d');
            $points[] = ['date' => $key, 'label' => $date->format($monthly ? 'Y.m' : 'm/d'), 'count' => (int) ($rows[$key] ?? 0)];
        }

        return ['period' => $period, 'has_data' => DB::table('site_visit_sessions')->where('started_at', '<=', $this->databaseTime($now))->exists(), 'points' => $points];
    }
}
