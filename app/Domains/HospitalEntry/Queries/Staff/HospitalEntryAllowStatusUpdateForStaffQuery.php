<?php

declare(strict_types=1);

namespace App\Domains\HospitalEntry\Queries\Staff;

use App\Domains\HospitalEntry\Models\HospitalEntry;
use Illuminate\Support\Collection;

final class HospitalEntryAllowStatusUpdateForStaffQuery
{
    /**
     * @param  array<int, int|string>  $entryIds
     * @return Collection<int, HospitalEntry>
     */
    public function getForUpdate(array $entryIds): Collection
    {
        $ids = $this->normalizeIds($entryIds);

        if ($ids === []) {
            return collect();
        }

        return HospitalEntry::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'allow_status']);
    }

    /**
     * @param  array<int, int|string>  $entryIds
     */
    public function updateAllowStatus(array $entryIds, string $allowStatus): int
    {
        $ids = $this->normalizeIds($entryIds);

        if ($ids === []) {
            return 0;
        }

        return HospitalEntry::query()
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
