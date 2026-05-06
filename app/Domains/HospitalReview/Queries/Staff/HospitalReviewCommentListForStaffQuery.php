<?php

namespace App\Domains\HospitalReview\Queries\Staff;

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
                'post_status',
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
                    ->orWhereHas('review', fn ($reviewQuery) => $reviewQuery->where('title', 'like', "%{$q}%"));
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

        if (! empty($filters['category_domain'])) {
            $builder->whereHas(
                'review',
                fn ($query) => $query->where('category_domain', (string) $filters['category_domain'])
            );
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
                $builder->whereHas('review.categories', function ($query) use ($normalizedCategoryIds, $filters): void {
                    $query->whereIn('categories.id', $normalizedCategoryIds);

                    if (! empty($filters['category_domain'])) {
                        $query->where('categories.domain', (string) $filters['category_domain']);
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
}
