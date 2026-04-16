<?php

namespace App\Domains\Talk\Queries\Staff;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Talk\Models\Talk;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * TalkListForStaffQuery 역할 정의.
 * 토크 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class TalkListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $include = $filters['include'] ?? [];

        $builder = Talk::query()
            ->select([
                'id',
                'author_id',
                'title',
                'content',
                'status',
                'is_visible',
                'is_pinned',
                'pinned_order',
                'view_count',
                'comment_count',
                'like_count',
                'save_count',
                'created_at',
                'updated_at',
            ]);

        if (is_array($include) && in_array('author', $include, true)) {
            $builder->with([
                'author:id,name,email',
            ]);
        }

        if (is_array($include) && in_array('categories', $include, true)) {
            $builder->with([
                'categories' => fn ($query) => $query
                    ->select(['categories.id', 'categories.name', 'categories.depth', 'categories.sort_order'])
                    ->orderBy('depth')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ]);
        }

        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $builder->where(function ($query) use ($q): void {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('content', 'like', "%{$q}%");
            });
        }

        if (is_array($filters['status'] ?? null) && $filters['status'] !== []) {
            $builder->whereIn('status', $filters['status']);
        }

        if (array_key_exists('is_visible', $filters) && $filters['is_visible'] !== null) {
            $builder->where('is_visible', (bool) $filters['is_visible']);
        }

        $metricColumns = [
            'like_count',
            'save_count',
            'comment_count',
            'view_count',
        ];
        $metric = $filters['metric'] ?? null;
        if (is_string($metric) && in_array($metric, $metricColumns, true)) {
            if ($filters['metric_min'] !== null) {
                $builder->where($metric, '>=', (int) $filters['metric_min']);
            }

            if ($filters['metric_max'] !== null) {
                $builder->where($metric, '<=', (int) $filters['metric_max']);
            }
        }

        if (! empty($filters['author_id'])) {
            $builder->where('author_id', (int) $filters['author_id']);
        }

        $categoryCodes = $filters['category_codes'] ?? null;
        if (is_array($categoryCodes) && $categoryCodes !== []) {
            $normalizedCategoryCodes = collect($categoryCodes)
                ->filter(static fn ($value): bool => is_string($value))
                ->map(static fn (string $value): string => trim($value))
                ->filter(static fn (string $value): bool => $value !== '')
                ->unique()
                ->values()
                ->all();

            if ($normalizedCategoryCodes === []) {
                $builder->whereRaw('1 = 0');
            } else {
                $builder->whereHas(
                    'categories',
                    fn ($query) => $query
                        ->where('categories.domain', Category::DOMAIN_HOSPITAL_COMMUNITY)
                        ->whereIn('categories.code', $normalizedCategoryCodes)
                );
            }
        }

        if (! empty($filters['start_date'])) {
            $builder->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $builder->whereDate('created_at', '<=', $filters['end_date']);
        }

        $builder->orderBy($filters['sort'] ?? 'id', $filters['direction'] ?? 'desc');

        return $builder->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
    }
}
