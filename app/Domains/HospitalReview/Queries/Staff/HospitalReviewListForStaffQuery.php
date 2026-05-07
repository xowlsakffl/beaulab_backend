<?php

namespace App\Domains\HospitalReview\Queries\Staff;

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
                'content',
                'cost',
                'rating',
                'status',
                'post_status',
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
                'beforeImages',
                'afterImages',
                'categories' => fn ($query) => $query
                    ->select(['categories.id', 'categories.code', 'categories.domain', 'categories.name', 'categories.full_path', 'categories.depth', 'categories.sort_order'])
                    ->orderBy('depth')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ]);

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

        if (is_array($filters['post_status'] ?? null) && $filters['post_status'] !== []) {
            $builder->whereIn('post_status', $filters['post_status']);
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
                $builder->whereHas('categories', function ($query) use ($normalizedCategoryIds): void {
                    $query
                        ->whereIn('categories.domain', HospitalReview::categoryDomains())
                        ->whereIn('categories.id', $normalizedCategoryIds);
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
}
