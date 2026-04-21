<?php

namespace App\Domains\Talk\Queries\Staff;

use App\Domains\Talk\Models\Talk;
use Illuminate\Support\Collection;

/**
 * 토크 다중 노출 상태 변경 DB 쿼리.
 */
final class TalkVisibilityBulkUpdateForStaffQuery
{
    /**
     * @param  array<int, int>  $talkIds
     * @return Collection<int, Talk>
     */
    public function getForUpdate(array $talkIds): Collection
    {
        $ids = $this->normalizeIds($talkIds);

        if ($ids === []) {
            return collect();
        }

        return Talk::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'status', 'post_status']);
    }

    /**
     * @param  array<int, int>  $talkIds
     */
    public function update(array $talkIds, string $status): int
    {
        $ids = $this->normalizeIds($talkIds);

        if ($ids === []) {
            return 0;
        }

        return Talk::query()
            ->whereIn('id', $ids)
            ->whereNotIn('post_status', Talk::VISIBILITY_CHANGE_LOCKED_POST_STATUSES)
            ->update(['status' => $status]);
    }

    /**
     * @param  array<int, int|string>  $talkIds
     * @return array<int, int>
     */
    private function normalizeIds(array $talkIds): array
    {
        return collect($talkIds)
            ->map(static fn (int|string $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
