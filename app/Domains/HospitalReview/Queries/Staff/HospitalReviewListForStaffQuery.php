<?php

namespace App\Domains\HospitalReview\Queries\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\HospitalReview\Models\HospitalReview;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * HospitalReviewListForStaffQuery 역할 정의.
 * 병의원 후기 도메인의 Query 계층으로, 관리자 목록 조회 조건을 캡슐화한다.
 */
final class HospitalReviewListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $builder = HospitalReview::query()
            ->select([
                'id',
                'author_id',
                'hospital_id',
                'doctor_id',
                'title',
                'cost',
                'rating',
                'status',
                'is_main_featured',
                'is_sub_featured',
                'view_count',
                'comment_count',
                'like_count',
                'save_count',
                'created_at',
                'updated_at',
            ])
            ->with([
                'author:id,name,nickname,email',
                'hospital:id,name',
                'hospital.businessRegistration:id,hospital_id,business_number',
                'doctor:id,name,position',
                'categories' => fn ($query) => $query
                    ->select(['categories.id', 'categories.code', 'categories.domain', 'categories.name', 'categories.full_path', 'categories.depth', 'categories.sort_order'])
                    ->orderBy('depth')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
                'contentReportState:id,target_type,target_id,report_status',
            ]);

        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $builder->where(function ($query) use ($q): void {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('content', 'like', "%{$q}%")
                    ->orWhereHas('author', function ($authorQuery) use ($q): void {
                        $authorQuery
                            ->where('name', 'like', "%{$q}%")
                            ->orWhere('nickname', 'like', "%{$q}%");
                    })
                    ->orWhereHas('hospital', fn ($hospitalQuery) => $hospitalQuery->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('doctor', fn ($doctorQuery) => $doctorQuery->where('name', 'like', "%{$q}%"));
            });
        }

        if (is_array($filters['status'] ?? null) && $filters['status'] !== []) {
            $builder->whereIn('status', $filters['status']);
        }

        if (is_array($filters['report_status'] ?? null) && $filters['report_status'] !== []) {
            $builder->whereHas(
                'contentReportState',
                fn ($query) => $query->whereIn('report_status', $filters['report_status'])
            );
        }

        if (! empty($filters['author_id'])) {
            $builder->where('author_id', (int) $filters['author_id']);
        }

        if (! empty($filters['hospital_id'])) {
            $builder->where('hospital_id', (int) $filters['hospital_id']);
        }

        if (! empty($filters['doctor_id'])) {
            $builder->where('doctor_id', (int) $filters['doctor_id']);
        }

        if (! empty($filters['category_domain'])) {
            $builder->where('category_domain', (string) $filters['category_domain']);
        }

        $categoryIds = $filters['category_ids'] ?? null;
        if (is_array($categoryIds) && $categoryIds !== []) {
            $normalizedCategoryIds = collect($categoryIds)
                ->map(static fn (int|string $value): int => (int) $value)
                ->filter(static fn (int $value): bool => $value > 0)
                ->unique()
                ->values()
                ->all();

            if ($normalizedCategoryIds === []) {
                $builder->whereRaw('1 = 0');
            } else {
                $expandedCategoryIds = $this->expandCategoryIdsWithDescendants($normalizedCategoryIds);

                $builder->whereHas('categories', function ($query) use ($expandedCategoryIds): void {
                    $query
                        ->where('categories.domain', Category::DOMAIN_HOSPITAL_MEDICAL)
                        ->whereIn('categories.id', $expandedCategoryIds);
                });
            }
        }

        $ratings = $filters['ratings'] ?? null;
        if (is_array($ratings) && $ratings !== []) {
            $normalizedRatings = collect($ratings)
                ->map(static fn (int|string $value): int => (int) $value)
                ->filter(static fn (int $value): bool => $value >= 1 && $value <= 5)
                ->unique()
                ->values()
                ->all();

            if ($normalizedRatings === []) {
                $builder->whereRaw('1 = 0');
            } else {
                $builder->whereIn('rating', $normalizedRatings);
            }
        }

        if ($filters['is_main_featured'] !== null) {
            $builder->where('is_main_featured', (bool) $filters['is_main_featured']);
        }

        if ($filters['is_sub_featured'] !== null) {
            $builder->where('is_sub_featured', (bool) $filters['is_sub_featured']);
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

        if (! empty($filters['start_date'])) {
            $builder->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $builder->whereDate('created_at', '<=', $filters['end_date']);
        }

        $builder->orderBy($filters['sort'] ?? 'id', $filters['direction'] ?? 'desc');

        return $builder->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
    }

    /**
     * @param  array<int, int>  $categoryIds
     * @return array<int, int>
     */
    private function expandCategoryIdsWithDescendants(array $categoryIds): array
    {
        $selectedCategories = Category::query()
            ->select(['id', 'domain', 'name', 'full_path'])
            ->whereIn('id', $categoryIds)
            ->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)
            ->get();

        if ($selectedCategories->isEmpty()) {
            return [];
        }

        return Category::query()
            ->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)
            ->where(function ($query) use ($selectedCategories): void {
                foreach ($selectedCategories as $selectedCategory) {
                    $pathPrefix = trim((string) ($selectedCategory->full_path ?: $selectedCategory->name));

                    $query->orWhere(function ($nested) use ($selectedCategory, $pathPrefix): void {
                        $nested->where('id', (int) $selectedCategory->id);

                        if ($pathPrefix !== '') {
                            $nested->orWhere('full_path', 'like', $pathPrefix . ' > %');
                        }
                    });
                }
            })
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();
    }
}
