<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Queries\Staff;

use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\Hospital\Models\Hospital;
use Illuminate\Support\Carbon;

final class HospitalSummaryForStaffQuery
{
    /**
     * @return array<string, int>
     */
    public function get(): array
    {
        $todayStart = Carbon::today();
        $last7DaysStart = Carbon::today()->subDays(6);
        $last30DaysStart = Carbon::now()->subDays(30);
        $last30RegistrationStart = Carbon::today()->subDays(29);
        $last1YearStart = Carbon::today()->subYear();

        return [
            'daily_visitors' => $this->visitedHospitalAccountCount($todayStart),
            'monthly_visitors' => $this->visitedHospitalAccountCount($last30DaysStart),
            'today_registered_hospitals' => $this->registeredHospitalCount($todayStart),
            'registered_within_7_days' => $this->registeredHospitalCount($last7DaysStart),
            'registered_within_30_days' => $this->registeredHospitalCount($last30RegistrationStart),
            'registered_within_1_year' => $this->registeredHospitalCount($last1YearStart),
            'dormant_hospitals' => AccountHospital::query()
                ->where('status', '!=', AccountHospital::STATUS_WITHDRAWN)
                ->whereHas('hospital', static fn ($query) => $query
                    ->where('status', '!=', Hospital::STATUS_WITHDRAWN))
                ->where(static fn ($query) => $query
                    ->whereNull('last_login_at')
                    ->orWhere('last_login_at', '<', $last30DaysStart))
                ->count(),
            'total_hospitals' => Hospital::query()
                ->where('status', '!=', Hospital::STATUS_WITHDRAWN)
                ->count(),
            'pending_review_hospitals' => Hospital::query()
                ->where('status', '!=', Hospital::STATUS_WITHDRAWN)
                ->where('allow_status', Hospital::ALLOW_PENDING)
                ->count(),
            'rejected_review_hospitals' => Hospital::query()
                ->where('status', '!=', Hospital::STATUS_WITHDRAWN)
                ->where('allow_status', Hospital::ALLOW_REJECTED)
                ->count(),
            'suspended_hospitals' => Hospital::query()
                ->where('status', Hospital::STATUS_SUSPENDED)
                ->count(),
            'withdrawn_hospitals' => Hospital::query()
                ->withTrashed()
                ->where(static fn ($query) => $query
                    ->where('status', Hospital::STATUS_WITHDRAWN)
                    ->orWhereNotNull('deleted_at'))
                ->count(),
        ];
    }

    private function visitedHospitalAccountCount(Carbon $since): int
    {
        return AccountHospital::query()
            ->where('status', '!=', AccountHospital::STATUS_WITHDRAWN)
            ->whereHas('hospital', static fn ($query) => $query
                ->where('status', '!=', Hospital::STATUS_WITHDRAWN))
            ->where('last_login_at', '>=', $since)
            ->count();
    }

    private function registeredHospitalCount(Carbon $since): int
    {
        return Hospital::query()
            ->where('status', '!=', Hospital::STATUS_WITHDRAWN)
            ->where('created_at', '>=', $since)
            ->count();
    }
}
