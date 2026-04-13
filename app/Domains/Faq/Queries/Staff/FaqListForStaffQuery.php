<?php

namespace App\Domains\Faq\Queries\Staff;

use App\Domains\Faq\Models\Faq;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * FaqListForStaffQuery 역할 정의.
 * FAQ 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class FaqListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Faq::query()
            ->select([
                'id',
                'channel',
                'question',
                'status',
                'sort_order',
                'view_count',
                'created_by_staff_id',
                'updated_by_staff_id',
                'created_at',
                'updated_at',
            ])
            ->with([
                'categories:id,name,domain,status,sort_order',
            ]);

        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $query->where(function (Builder $builder) use ($q): void {
                $builder->where('question', 'like', "%{$q}%")
                    ->orWhere('content', 'like', "%{$q}%");
            });
        }

        if (is_array($filters['channel'] ?? null) && $filters['channel'] !== []) {
            $query->whereIn('channel', $filters['channel']);
        }

        if (is_array($filters['status'] ?? null) && $filters['status'] !== []) {
            $query->whereIn('status', $filters['status']);
        }

        if (! empty($filters['category_id'])) {
            $categoryId = (int) $filters['category_id'];
            $query->whereHas('categories', static function (Builder $builder) use ($categoryId): void {
                $builder->where('categories.id', $categoryId);
            });
        }

        $sort = $filters['sort'] ?? null;
        $direction = $filters['direction'] ?? 'desc';

        if ($sort !== null) {
            $query->orderBy($sort, $direction);
            if ($sort !== 'id') {
                $query->orderByDesc('id');
            }
        } else {
            $query->orderBy('sort_order')
                ->orderByDesc('id');
        }

        return $query
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();
    }
}
