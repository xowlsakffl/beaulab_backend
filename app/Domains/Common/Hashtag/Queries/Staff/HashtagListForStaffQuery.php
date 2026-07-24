<?php

namespace App\Domains\Common\Hashtag\Queries\Staff;

use App\Common\Support\DateRangeFilter;
use App\Domains\Common\Hashtag\Models\Hashtag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * HashtagListForStaffQuery 역할 정의.
 * 공통 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class HashtagListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 50;
        $q = $filters['q'] ?? null;
        $statuses = $filters['statuses'] ?? [];
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $updatedStartDate = $filters['updated_start_date'] ?? null;
        $updatedEndDate = $filters['updated_end_date'] ?? null;
        $sort = $filters['sort'] ?? 'id';
        $direction = $filters['direction'] ?? 'desc';

        $builder = Hashtag::query()
            ->select([
                'id',
                'name',
                'normalized_name',
                'status',
                'usage_count',
                'created_at',
                'updated_at',
            ])
            ->selectSub(
                DB::table('hashtaggables')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('hashtaggables.hashtag_id', 'hashtags.id'),
                'assignment_count',
            )
            ->search($q)
            ->statusIn(is_array($statuses) ? $statuses : []);

        DateRangeFilter::apply($builder, 'created_at', $startDate, $endDate);
        DateRangeFilter::apply($builder, 'updated_at', $updatedStartDate, $updatedEndDate);

        $builder->orderBy($sort, $direction);

        if ($sort !== 'id') {
            $builder->orderByDesc('id');
        }

        return $builder
            ->paginate((int) $perPage)
            ->withQueryString();
    }
}
