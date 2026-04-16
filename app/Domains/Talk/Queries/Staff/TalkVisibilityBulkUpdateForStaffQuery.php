<?php

namespace App\Domains\Talk\Queries\Staff;

use App\Domains\Talk\Models\Talk;

/**
 * 토크 다중 노출 상태 변경 DB 쿼리.
 */
final class TalkVisibilityBulkUpdateForStaffQuery
{
    /**
     * @param  array<int, int>  $talkIds
     */
    public function update(array $talkIds, bool $isVisible): int
    {
        $ids = collect($talkIds)
            ->map(static fn (int|string $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return 0;
        }

        return Talk::query()
            ->whereIn('id', $ids)
            ->update(['is_visible' => $isVisible]);
    }
}
