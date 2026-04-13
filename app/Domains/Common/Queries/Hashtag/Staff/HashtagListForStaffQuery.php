<?php

namespace App\Domains\Common\Queries\Hashtag\Staff;

use App\Domains\Common\Models\Hashtag\Hashtag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
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

        return $this->buildQuery($filters)
            ->paginate((int) $perPage)
            ->withQueryString();
    }

    private function buildQuery(array $filters): Builder
    {
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

        if (Hashtag::supportsUsageCount()) {
            $builder->addSelect('usage_count');
        }

        if (Hashtag::supportsStatus()) {
            $builder->addSelect('status');
        }

        if (is_string($startDate) && $startDate !== '') {
            $builder->whereDate('created_at', '>=', $startDate);
        }

        if (is_string($endDate) && $endDate !== '') {
            $builder->whereDate('created_at', '<=', $endDate);
        }

        if (is_string($updatedStartDate) && $updatedStartDate !== '') {
            $builder->whereDate('updated_at', '>=', $updatedStartDate);
        }

        if (is_string($updatedEndDate) && $updatedEndDate !== '') {
            $builder->whereDate('updated_at', '<=', $updatedEndDate);
        }

        if ($sort === 'usage_count' && !Hashtag::supportsUsageCount()) {
            $sort = 'id';
            $direction = 'desc';
        }

        if ($sort === 'status' && !Hashtag::supportsStatus()) {
            $sort = 'id';
            $direction = 'desc';
        }

        $builder->orderBy($sort, $direction);

        if ($sort !== 'id') {
            $builder->orderByDesc('id');
        }

        return $builder;
    }
}
