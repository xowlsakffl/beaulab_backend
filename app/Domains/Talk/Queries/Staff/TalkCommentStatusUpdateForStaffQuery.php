<?php

namespace App\Domains\Talk\Queries\Staff;

use App\Domains\Talk\Models\TalkComment;
use Illuminate\Support\Collection;

/**
 * 토크 댓글 다중 노출 상태 변경 DB 쿼리.
 */
final class TalkCommentStatusUpdateForStaffQuery
{
    /**
     * @param  array<int, int>  $commentIds
     * @return Collection<int, TalkComment>
     */
    public function getForUpdate(array $commentIds): Collection
    {
        $ids = $this->normalizeIds($commentIds);

        if ($ids === []) {
            return collect();
        }

        return TalkComment::query()
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

        return TalkComment::query()
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
