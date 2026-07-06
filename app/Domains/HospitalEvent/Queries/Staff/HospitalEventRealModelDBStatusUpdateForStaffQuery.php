<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Queries\Staff;

use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use Illuminate\Support\Collection;

final class HospitalEventRealModelDBStatusUpdateForStaffQuery
{
    /**
     * @param  array<int, int|string>  $applicationIds
     * @return Collection<int, HospitalEventRealModelDB>
     */
    public function getForUpdate(array $applicationIds): Collection
    {
        $ids = $this->normalizeIds($applicationIds);

        if ($ids === []) {
            return collect();
        }

        return HospitalEventRealModelDB::query()
            ->with('event:id,hospital_id,hospital_status,admin_status,allow_status')
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get([
                'id',
                'hospital_id',
                'hospital_event_id',
                'status',
            ]);
    }

    /**
     * @param  array<int, int|string>  $applicationIds
     */
    public function updateStatus(array $applicationIds, string $status): int
    {
        $ids = $this->normalizeIds($applicationIds);

        if ($ids === []) {
            return 0;
        }

        return HospitalEventRealModelDB::query()
            ->whereIn('id', $ids)
            ->update(['status' => $status]);
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return array<int, int>
     */
    private function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->map(static fn (int|string $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
