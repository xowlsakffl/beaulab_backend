<?php

namespace App\Domains\HospitalEventAd\Queries\Staff;

use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use Illuminate\Support\Collection;

final class HospitalEventAdStateUpdateForStaffQuery
{
    /**
     * @param  array<int, int|string>  $adIds
     * @return Collection<int, HospitalEventAd>
     */
    public function getForUpdate(array $adIds): Collection
    {
        $ids = $this->normalizeIds($adIds);

        if ($ids === []) {
            return collect();
        }

        return HospitalEventAd::query()
            ->with([
                'hospital:id,name,allow_status,status',
                'hospitalEvent:id,hospital_id,name,allow_status,admin_status',
                'categories:id,code,name,full_path,depth',
                'adImage',
            ])
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'hospital_id', 'hospital_event_id', 'placement', 'start_at', 'allow_status']);
    }

    /**
     * @param  array<int, int|string>  $adIds
     */
    public function updateAllowStatus(array $adIds, string $allowStatus): int
    {
        $ids = $this->normalizeIds($adIds);

        if ($ids === []) {
            return 0;
        }

        return HospitalEventAd::query()
            ->whereIn('id', $ids)
            ->update(['allow_status' => $allowStatus]);
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
