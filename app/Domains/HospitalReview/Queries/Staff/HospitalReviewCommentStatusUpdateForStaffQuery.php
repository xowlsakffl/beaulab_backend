<?php

namespace App\Domains\HospitalReview\Queries\Staff;

use App\Domains\HospitalReview\Models\HospitalReviewComment;
use Illuminate\Support\Collection;

final class HospitalReviewCommentStatusUpdateForStaffQuery
{
    /**
     * @param  array<int, int>  $commentIds
     * @return Collection<int, HospitalReviewComment>
     */
    public function getForUpdate(array $commentIds): Collection
    {
        $ids = $this->normalizeIds($commentIds);

        if ($ids === []) {
            return collect();
        }

        return HospitalReviewComment::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'status']);
    }

    /**
     * @param  array<int, int>  $commentIds
     */
    public function update(array $commentIds, string $status): int
    {
        $ids = $this->normalizeIds($commentIds);

        if ($ids === []) {
            return 0;
        }

        return HospitalReviewComment::query()
            ->whereIn('id', $ids)
            ->update(['status' => $status]);
    }

    /**
     * @param  array<int, int|string>  $commentIds
     * @return array<int, int>
     */
    private function normalizeIds(array $commentIds): array
    {
        return collect($commentIds)
            ->map(static fn (int|string $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
