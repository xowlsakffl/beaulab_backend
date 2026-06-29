<?php

declare(strict_types=1);

namespace App\Domains\HospitalEntry\Queries\Staff;

use App\Domains\HospitalEntry\Models\HospitalEntry;

final class HospitalEntrySummaryForStaffQuery
{
    /**
     * @return array<string, int>
     */
    public function get(): array
    {
        $counts = HospitalEntry::query()
            ->selectRaw('allow_status, COUNT(*) as aggregate_count')
            ->groupBy('allow_status')
            ->pluck('aggregate_count', 'allow_status');

        return [
            'pending_entries' => (int) ($counts[HospitalEntry::ALLOW_PENDING] ?? 0),
            'rejected_entries' => (int) ($counts[HospitalEntry::ALLOW_REJECTED] ?? 0),
            'approved_entries' => (int) ($counts[HospitalEntry::ALLOW_APPROVED] ?? 0),
        ];
    }
}
