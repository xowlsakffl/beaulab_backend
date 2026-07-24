<?php

namespace App\Domains\Common\Hashtag\Queries\Staff;

use App\Domains\Common\Hashtag\Models\Hashtag;
use Illuminate\Support\Facades\DB;

/**
 * HashtagUsageCountSyncForStaffQuery 역할 정의.
 * 공통 도메인의 Query 계층으로, 해시태그 사용수 재계산 DB 작업을 캡슐화한다.
 */
final class HashtagUsageCountSyncForStaffQuery
{
    /**
     * @param  array<int, int|string>  $hashtagIds
     */
    public function sync(array $hashtagIds): void
    {
        $ids = collect($hashtagIds)
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return;
        }

        $countsById = DB::table('hashtaggables')
            ->selectRaw('hashtag_id, COUNT(*) as aggregate_count')
            ->whereIn('hashtag_id', $ids)
            ->groupBy('hashtag_id')
            ->pluck('aggregate_count', 'hashtag_id');

        foreach ($ids as $id) {
            Hashtag::query()
                ->whereKey($id)
                ->update([
                    'usage_count' => (int) ($countsById[$id] ?? 0),
                ]);
        }
    }
}
