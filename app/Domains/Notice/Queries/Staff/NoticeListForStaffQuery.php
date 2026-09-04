<?php

namespace App\Domains\Notice\Queries\Staff;

use App\Common\Support\DateRangeFilter;
use App\Domains\Notice\Models\Notice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * NoticeListForStaffQuery 역할 정의.
 * 공지사항 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class NoticeListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Notice::query()
            ->select([
                'id',
                'channel',
                'title',
                'status',
                'view_count',
                'created_by_staff_id',
                'created_at',
            ])
            ->with([
                'creator:id,name,email',
            ]);

        if (isset($filters['q']) && trim((string) $filters['q']) !== '') {
            $q = trim((string) $filters['q']);
            $query->where(function (Builder $builder) use ($q): void {
                $builder->where('title', 'like', "%{$q}%")
                    ->orWhereHas('creator', function (Builder $staffQuery) use ($q): void {
                        $staffQuery->where('name', 'like', "%{$q}%")
                            ->orWhere('nickname', 'like', "%{$q}%");
                    });

                if (ctype_digit($q)) {
                    $builder->orWhere('id', $q);
                }
            });
        }

        if (is_array($filters['channel'] ?? null) && $filters['channel'] !== []) {
            $query->whereIn('channel', $filters['channel']);
        }

        if (is_array($filters['status'] ?? null) && $filters['status'] !== []) {
            $query->whereIn('status', $filters['status']);
        }

        DateRangeFilter::apply($query, 'created_at', $filters['start_date'] ?? null, $filters['end_date'] ?? null);
        $sort = $filters['sort'] ?? null;
        $direction = $filters['direction'] ?? 'desc';

        if ($sort !== null) {
            $query->orderBy($sort, $direction);
            if ($sort !== 'id') {
                $query->orderByDesc('id');
            }
        } else {
            $query->orderByDesc('id');
        }

        return $query
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();
    }
}
