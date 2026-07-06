<?php

namespace App\Domains\HospitalEvent\Queries\Staff;

use App\Domains\HospitalEvent\Models\HospitalEvent;
use Illuminate\Support\Collection;

final class HospitalEventStateUpdateForStaffQuery
{
    /**
     * @param  array<int, int|string>  $eventIds
     * @return Collection<int, HospitalEvent>
     */
    public function getForUpdate(array $eventIds): Collection
    {
        $ids = $this->normalizeIds($eventIds);

        if ($ids === []) {
            return collect();
        }

        return HospitalEvent::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'admin_status', 'allow_status']);
    }

    /**
     * @param  array<int, int|string>  $eventIds
     */
    public function updateAdminStatus(array $eventIds, string $adminStatus): int
    {
        $ids = $this->normalizeIds($eventIds);

        if ($ids === []) {
            return 0;
        }

        return HospitalEvent::query()
            ->whereIn('id', $ids)
            ->update(['admin_status' => $adminStatus]);
    }

    /**
     * @param  array<int, int|string>  $eventIds
     */
    public function updateAllowStatus(array $eventIds, string $allowStatus): int
    {
        $ids = $this->normalizeIds($eventIds);

        if ($ids === []) {
            return 0;
        }

        return HospitalEvent::query()
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
