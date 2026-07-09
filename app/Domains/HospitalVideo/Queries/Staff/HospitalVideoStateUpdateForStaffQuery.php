<?php

namespace App\Domains\HospitalVideo\Queries\Staff;

use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Support\Collection;

final class HospitalVideoStateUpdateForStaffQuery
{
    /**
     * @param  array<int, int|string>  $videoIds
     * @return Collection<int, HospitalVideo>
     */
    public function getForUpdate(array $videoIds): Collection
    {
        $ids = $this->normalizeIds($videoIds);

        if ($ids === []) {
            return collect();
        }

        return HospitalVideo::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'admin_status']);
    }

    /**
     * @param  array<int, int|string>  $videoIds
     */
    public function updateAdminStatus(array $videoIds, string $adminStatus): int
    {
        $ids = $this->normalizeIds($videoIds);

        if ($ids === []) {
            return 0;
        }

        return HospitalVideo::query()
            ->whereIn('id', $ids)
            ->update(['admin_status' => $adminStatus]);
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
