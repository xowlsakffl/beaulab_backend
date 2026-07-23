<?php

declare(strict_types=1);

namespace App\Domains\HospitalEntry\Queries\Staff;

use App\Domains\Common\Cache\Support\StaffSummaryCache;
use App\Domains\HospitalEntry\Models\HospitalEntry;

final class HospitalEntrySummaryForStaffQuery
{
    /**
     * @return array<string, int>
     */
    public function get(): array
    {
        return StaffSummaryCache::remember(
            StaffSummaryCache::DOMAIN_HOSPITAL_ENTRY,
            fn (): array => $this->uncachedSummary(),
        );
    }

    /**
     * @return array<string, int>
     */
    private function uncachedSummary(): array
    {
        $counts = HospitalEntry::query()
            ->selectRaw('allow_status, COUNT(*) as aggregate_count')
            ->groupBy('allow_status')
            ->pluck('aggregate_count', 'allow_status');

        return [
            'pending_entries' => (int) ($counts[HospitalEntry::ALLOW_PENDING] ?? 0),
            'reviewing_entries' => (int) ($counts[HospitalEntry::ALLOW_REVIEWING] ?? 0),
            'rejected_entries' => (int) ($counts[HospitalEntry::ALLOW_REJECTED] ?? 0),
            'approved_entries' => (int) ($counts[HospitalEntry::ALLOW_APPROVED] ?? 0),
        ];
    }
}
