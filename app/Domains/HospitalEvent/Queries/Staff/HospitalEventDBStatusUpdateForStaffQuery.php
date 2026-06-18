<?php

namespace App\Domains\HospitalEvent\Queries\Staff;

use App\Domains\HospitalEvent\Models\HospitalEventDB;
use Illuminate\Support\Collection;

final class HospitalEventDBStatusUpdateForStaffQuery
{
    /**
     * @param  array<int, int|string>  $consultationIds
     * @return Collection<int, HospitalEventDB>
     */
    public function getForUpdate(array $consultationIds): Collection
    {
        $ids = $this->normalizeIds($consultationIds);

        if ($ids === []) {
            return collect();
        }

        return HospitalEventDB::query()
            ->with('event:id,hospital_id,status,allow_status')
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get([
                'id',
                'hospital_id',
                'hospital_event_id',
                'phone_normalized',
                'status',
                'allow_status',
                'contacted_at',
                'confirmed_at',
                'duplicated_at',
            ]);
    }

    /**
     * @param  array<int, int|string>  $consultationIds
     */
    public function updateStatus(array $consultationIds, array $values): int
    {
        $ids = $this->normalizeIds($consultationIds);

        if ($ids === []) {
            return 0;
        }

        return HospitalEventDB::query()
            ->whereIn('id', $ids)
            ->update($values);
    }

    /**
     * @param  array<int, int|string>  $consultationIds
     */
    public function updateAllowStatus(array $consultationIds, array $values): int
    {
        $ids = $this->normalizeIds($consultationIds);

        if ($ids === []) {
            return 0;
        }

        return HospitalEventDB::query()
            ->whereIn('id', $ids)
            ->update($values);
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
