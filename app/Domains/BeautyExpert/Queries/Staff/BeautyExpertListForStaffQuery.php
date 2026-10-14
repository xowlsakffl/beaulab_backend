<?php

namespace App\Domains\BeautyExpert\Queries\Staff;

use App\Domains\BeautyExpert\Models\BeautyExpert;
use App\Domains\Common\Category\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * BeautyExpertListForStaffQuery 역할 정의.
 * 뷰티 전문가 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class BeautyExpertListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $categoryIds = $filters['category_ids'] ?? null;
        $include = $filters['include'] ?? [];

        $builder = BeautyExpert::query()->select([
            'id', 'beauty_id', 'name', 'position', 'sort_order',
            'allow_status', 'status', 'created_at', 'updated_at',
        ]);

        if (is_array($include) && in_array('categories', $include, true)) {
            $builder->with([
                'categories' => fn ($query) => $query
                    ->select(['categories.id', 'categories.name', 'categories.depth', 'categories.sort_order'])
                    ->orderBy('depth')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ]);
        }

        if (! empty($filters['beauty_id'])) {
            $builder->where('beauty_id', (int) $filters['beauty_id']);
        }

        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $builder->where(function ($query) use ($q): void {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('position', 'like', "%{$q}%");
            });
        }

        if (is_array($filters['status'] ?? null) && $filters['status'] !== []) {
            $builder->whereIn('status', $filters['status']);
        }

        if (is_array($filters['allow_status'] ?? null) && $filters['allow_status'] !== []) {
            $builder->whereIn('allow_status', $filters['allow_status']);
        }

        if (is_array($categoryIds) && $categoryIds !== []) {
            $expandedCategoryIds = $this->expandWithDescendants($categoryIds);

            if ($expandedCategoryIds === []) {
                $builder->whereRaw('1 = 0');
            } else {
                $builder->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $expandedCategoryIds));
            }
        }

        $builder->orderBy($filters['sort'] ?? 'id', $filters['direction'] ?? 'desc');

        return $builder->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
    }

    /**
     * @param  array<int, int|string>  $categoryIds
     * @return array<int, int>
     */
    private function expandWithDescendants(array $categoryIds): array
    {
        $selectedCategoryIds = collect($categoryIds)
            ->map(static fn (int|string $categoryId): int => (int) $categoryId)
            ->filter(static fn (int $categoryId): bool => $categoryId > 0)
            ->unique()
            ->values()
            ->all();

        if ($selectedCategoryIds === []) {
            return [];
        }

        return \App\Domains\Common\Category\Support\CategoryHierarchy::selectedAndDescendants(Category::DOMAIN_BEAUTY_EXPERT, $selectedCategoryIds);
    }
}
