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
        $hospitalCounts = Hospital::query()
            ->withTrashed()
            ->selectRaw(
                <<<'SQL'
                SUM(CASE WHEN deleted_at IS NULL AND status != ? AND allow_status = ? THEN 1 ELSE 0 END) AS pending_review_hospitals,
                SUM(CASE WHEN deleted_at IS NULL AND status != ? AND allow_status = ? THEN 1 ELSE 0 END) AS rejected_review_hospitals,
                SUM(CASE WHEN deleted_at IS NULL AND status = ? THEN 1 ELSE 0 END) AS suspended_hospitals,
                SUM(CASE WHEN status = ? OR deleted_at IS NOT NULL THEN 1 ELSE 0 END) AS withdrawn_hospitals
                SQL,
                [
                    Hospital::STATUS_WITHDRAWN,
                    Hospital::ALLOW_PENDING,
                    Hospital::STATUS_WITHDRAWN,
                    Hospital::ALLOW_REJECTED,
                    Hospital::STATUS_SUSPENDED,
                    Hospital::STATUS_WITHDRAWN,
                ],
            )
            ->first();

        return [
            'dormant_hospitals' => AccountHospital::query()
                ->where('status', '!=', AccountHospital::STATUS_WITHDRAWN)
                ->whereHas('hospital', static fn ($query) => $query
                    ->where('status', '!=', Hospital::STATUS_WITHDRAWN))
                ->where(static fn ($query) => $query
                    ->whereNull('last_login_at')
                    ->orWhere('last_login_at', '<', $last30DaysStart))
                ->count(),
            'pending_review_hospitals' => (int) ($hospitalCounts?->pending_review_hospitals ?? 0),
            'rejected_review_hospitals' => (int) ($hospitalCounts?->rejected_review_hospitals ?? 0),
            'suspended_hospitals' => (int) ($hospitalCounts?->suspended_hospitals ?? 0),
            'withdrawn_hospitals' => (int) ($hospitalCounts?->withdrawn_hospitals ?? 0),
        ];
    }
}
