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
        $baseQuery = HospitalEntry::query();

        return [
            'pending_entries' => (clone $baseQuery)
                ->where('allow_status', HospitalEntry::ALLOW_PENDING)
                ->count(),
            'rejected_entries' => (clone $baseQuery)
                ->where('allow_status', HospitalEntry::ALLOW_REJECTED)
                ->count(),
            'approved_entries' => (clone $baseQuery)
                ->where('allow_status', HospitalEntry::ALLOW_APPROVED)
                ->count(),
        ];
    }
}
