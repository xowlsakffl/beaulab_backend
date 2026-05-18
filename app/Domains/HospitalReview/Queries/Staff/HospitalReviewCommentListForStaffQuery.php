<?php

namespace App\Domains\HospitalReview\Queries\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class HospitalReviewCommentListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $builder = HospitalReviewComment::query()
            ->select([
                'id',
                'hospital_review_id',
                'parent_id',
                'author_id',
                'content',
                'status',
                'like_count',
                'created_at',
                'updated_at',
            ])
            ->with([
                'review:id,title,category_domain',
                'author:id,name,nickname,email',
                'review.categories' => fn ($categoryQuery) => $categoryQuery
                    ->select(['categories.id', 'categories.code', 'categories.domain', 'categories.name', 'categories.full_path', 'categories.depth', 'categories.sort_order'])
                    ->orderBy('depth')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
                'contentReportState:id,target_type,target_id,report_status',
            ]);

        if (! empty($filters['hospital_review_id'])) {
            $builder->where('hospital_review_id', (int) $filters['hospital_review_id']);
        }

        if (array_key_exists('parent_id', $filters) && $filters['parent_id'] !== null) {
            if ((int) $filters['parent_id'] === 0) {
                $builder->whereNull('parent_id');
            } else {
                $builder->where('parent_id', (int) $filters['parent_id']);
            }
        }

        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $builder->where(function ($query) use ($q): void {
                $query->where('content', 'like', "%{$q}%")
                    ->orWhereHas('author', fn ($authorQuery) => $authorQuery
                        ->where('name', 'like', "%{$q}%")
                        ->orWhere('nickname', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%"))
                    ->orWhereHas('review', function ($reviewQuery) use ($q): void {
                        $reviewQuery->where('title', 'like', "%{$q}%");

                        if (ctype_digit($q)) {
                            $reviewQuery->orWhere('id', (int) $q);
                        }
                    });

                if (ctype_digit($q)) {
                    $query->orWhere('id', (int) $q);
                }
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

        if (! empty($filters['category_domain'])) {
            $builder->whereHas(
                'review',
                fn ($query) => $query->where('category_domain', (string) $filters['category_domain'])
            );
        }

        $categoryIds = $filters['category_ids'] ?? null;
        if (is_array($categoryIds) && $categoryIds !== []) {
            $categoryDomain = (string) ($filters['category_domain'] ?? '');
            $normalizedCategoryIds = collect($categoryIds)
                ->map(static fn (int|string $value): int => (int) $value)
                ->filter(static fn (int $value): bool => $value > 0)
                ->unique()
                ->values()
                ->all();

            if ($normalizedCategoryIds === []) {
                $builder->whereRaw('1 = 0');
            } else {
                $expandedCategoryIds = $this->expandCategoryIdsWithDescendants($normalizedCategoryIds, $categoryDomain);

                $builder->whereHas('review.categories', function ($query) use ($expandedCategoryIds, $categoryDomain): void {
                    $query->whereIn('categories.id', $expandedCategoryIds);

                    if ($categoryDomain !== '') {
                        $query->where('categories.domain', $categoryDomain);
                    }
                });
            }
        }

        if ($filters['metric_min'] !== null) {
            $builder->where('like_count', '>=', (int) $filters['metric_min']);
        }

        if ($filters['metric_max'] !== null) {
            $builder->where('like_count', '<=', (int) $filters['metric_max']);
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
    private function expandCategoryIdsWithDescendants(array $categoryIds, string $categoryDomain): array
    {
        if ($categoryDomain === '') {
            return $categoryIds;
        }

        return Category::query()
            ->where('domain', $categoryDomain)
            ->where(function ($query) use ($categoryIds, $categoryDomain): void {
                $query
                    ->whereIn('id', $categoryIds)
                    ->orWhereIn('parent_id', $categoryIds)
                    ->orWhereIn('parent_id', function ($subQuery) use ($categoryIds, $categoryDomain): void {
                        $subQuery
                            ->select('id')
                            ->from('categories')
                            ->where('domain', $categoryDomain)
                            ->whereIn('parent_id', $categoryIds);
                    });
            })
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();
    }
}
