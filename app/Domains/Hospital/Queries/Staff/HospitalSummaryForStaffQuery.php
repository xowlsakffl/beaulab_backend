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
        $last30DaysStart = Carbon::now()->subDays(30);

        return [
            'dormant_hospitals' => AccountHospital::query()
                ->where('status', '!=', AccountHospital::STATUS_WITHDRAWN)
                ->whereHas('hospital', static fn ($query) => $query
                    ->where('status', '!=', Hospital::STATUS_WITHDRAWN))
                ->where(static fn ($query) => $query
                    ->whereNull('last_login_at')
                    ->orWhere('last_login_at', '<', $last30DaysStart))
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
}
