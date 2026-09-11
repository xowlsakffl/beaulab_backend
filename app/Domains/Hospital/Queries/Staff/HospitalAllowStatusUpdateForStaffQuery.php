<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Queries\Staff;

use App\Domains\Hospital\Models\Hospital;
use Illuminate\Support\Collection;

final class HospitalAllowStatusUpdateForStaffQuery
{
    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, Hospital>
     */
    public function getForUpdate(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return Hospital::query()
            ->whereIn('id', $ids)
            ->lockForUpdate()
            ->get(['id', 'allow_status', 'ad_reception_phone_1']);
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function updateAllowStatus(array $ids, string $allowStatus): int
    {
        if ($ids === []) {
            return 0;
        }

        return Hospital::query()
            ->whereIn('id', $ids)
            ->update(['allow_status' => $allowStatus]);
    }
}
