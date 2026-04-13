<?php

namespace App\Domains\Common\Queries\Hashtag\Staff;

use App\Domains\Common\Models\Hashtag\Hashtag;
use Illuminate\Support\Facades\DB;

/**
 * HashtagGetForStaffQuery 역할 정의.
 * 공통 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class HashtagGetForStaffQuery
{
    public function get(Hashtag $hashtag): Hashtag
    {
        $query = Hashtag::query()
            ->select([
                'id',
                'name',
                'normalized_name',
                'created_at',
                'updated_at',
            ])
            ->selectSub(
                DB::table('hashtaggables')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('hashtaggables.hashtag_id', 'hashtags.id'),
                'assignment_count',
            );

        if (Hashtag::supportsUsageCount()) {
            $query->addSelect('usage_count');
        }

        if (Hashtag::supportsStatus()) {
            $query->addSelect('status');
        }

        return $query->findOrFail($hashtag->id);
    }
}
