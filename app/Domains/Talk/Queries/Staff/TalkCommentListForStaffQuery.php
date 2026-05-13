<?php

namespace App\Domains\Talk\Queries\Staff;

use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class TalkCommentListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $builder = TalkComment::query()
            ->select([
                'id',
                'talk_id',
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
                'talk:id,title',
                'author:id,name,nickname,email',
                'mentions.mentionedUser',
                'talk.categories' => fn ($categoryQuery) => $categoryQuery
                    ->select(['categories.id', 'categories.code', 'categories.domain', 'categories.name', 'categories.full_path', 'categories.depth', 'categories.sort_order'])
                    ->orderBy('depth')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ]);

        if (! empty($filters['talk_id'])) {
            $builder->where('talk_id', (int) $filters['talk_id']);
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
                    ->orWhereHas('talk', fn ($talkQuery) => $talkQuery->where('title', 'like', "%{$q}%"))
                    ->orWhereHas('author', fn ($authorQuery) => $authorQuery->where('nickname', 'like', "%{$q}%"));
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
                $builder->whereHas(
                    'talk.categories',
                    fn ($query) => $query
                        ->where('categories.domain', Talk::CATEGORY_DOMAIN)
                        ->whereIn('categories.id', $normalizedCategoryIds)
                );
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
