<?php

namespace App\Domains\Hospital\Queries\Staff;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Hospital\Models\Hospital;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class HospitalListForStaffQuery
{
    /**
     * 뷰랩 전용 병원 리스트
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $q         = $filters['q'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate   = $filters['end_date'] ?? null;
        $status    = $filters['status'] ?? null;
        $allow     = $filters['allow_status'] ?? null;
        $categoryIds = $filters['category_ids'] ?? null;
        $include = $filters['include'] ?? [];
        $sort      = $filters['sort'] ?? 'id';
        $direction = $filters['direction'] ?? 'desc';
        $perPage   = $filters['per_page'] ?? 15;

        // 필요한 컬러만 정의
        $builder = Hospital::query()->select([
            'id',
            'name',
            'address',
            'tel',
            'view_count',
            'allow_status',
            'status',
            'created_at',
            'updated_at',
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

        // 검색: name / address / tel LIKE 검색
        if ($q) {
            $builder->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%")
                    ->orWhere('tel', 'like', "%{$q}%");
            });
        }

        // 등록일(created_at) 기간 필터
        if ($startDate && $endDate) {
            $builder->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate);
        } elseif ($startDate) {
            $builder->whereDate('created_at', '>=', $startDate);
        } elseif ($endDate) {
            $builder->whereDate('created_at', '<=', $endDate);
        }

        // 필터(status, allow_status)
        if (is_array($status) && $status !== []) {
            $builder->whereIn('status', $status);
        }

        if (is_array($allow) && $allow !== []) {
            $builder->whereIn('allow_status', $allow);
        }

        if (is_array($categoryIds) && $categoryIds !== []) {
            $expandedCategoryIds = $this->expandWithDescendants($categoryIds);

            if ($expandedCategoryIds === []) {
                $builder->whereRaw('1 = 0');
            } else {
                $builder->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $expandedCategoryIds));
            }
        }

        // 정렬
        $builder->orderBy($sort, $direction);

        return $builder->paginate($perPage)->withQueryString();
    }

    /**
     * @param array<int, int|string> $categoryIds
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

        $selectedCategories = Category::query()
            ->select(['id', 'domain', 'name', 'full_path'])
            ->whereIn('id', $selectedCategoryIds)
            ->whereIn('domain', [Category::DOMAIN_HOSPITAL_TREATMENT, Category::DOMAIN_HOSPITAL_SURGERY])
            ->get();

        if ($selectedCategories->isEmpty()) {
            return [];
        }

        return Category::query()
            ->select('id')
            ->where(function ($query) use ($selectedCategories): void {
                foreach ($selectedCategories as $selectedCategory) {
                    $pathPrefix = trim((string) ($selectedCategory->full_path ?: $selectedCategory->name));

                    $query->orWhere(function ($nested) use ($selectedCategory, $pathPrefix): void {
                        $nested->where('domain', (string) $selectedCategory->domain)
                            ->where(function ($pathQuery) use ($selectedCategory, $pathPrefix): void {
                                $pathQuery->where('id', (int) $selectedCategory->id);

                                if ($pathPrefix !== '') {
                                    $pathQuery->orWhere('full_path', 'like', $pathPrefix . ' > %');
                                }
                            });
                    });
                }
            })
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
