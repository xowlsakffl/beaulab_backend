<?php

namespace App\Domains\HospitalReview\Queries\Staff;

use App\Domains\HospitalReview\Models\HospitalReview;
use Illuminate\Support\Collection;

final class HospitalReviewStatusUpdateForStaffQuery
{
    /**
     * @param  array<int, int>  $reviewIds
     * @return Collection<int, HospitalReview>
     */
    public function getForUpdate(array $reviewIds): Collection
    {
        $ids = $this->normalizeIds($reviewIds);

        if ($ids === []) {
            return collect();
        }

        return HospitalReview::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'status']);
    }

    /**
     * @param  array<int, int>  $reviewIds
     */
    public function update(array $reviewIds, string $status): int
    {
        $ids = $this->normalizeIds($reviewIds);

        if ($ids === []) {
            return 0;
        }

        return HospitalReview::query()
            ->whereIn('id', $ids)
            ->update(['status' => $status]);
    }

    /**
     * @param  array<int, int|string>  $reviewIds
     * @return array<int, int>
     */
    private function normalizeIds(array $reviewIds): array
    {
        return collect($reviewIds)
            ->map(static fn (int|string $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
