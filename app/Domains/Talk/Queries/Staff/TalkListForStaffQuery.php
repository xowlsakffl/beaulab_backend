<?php

namespace App\Domains\Talk\Queries\Staff;

use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * TalkListForStaffQuery 역할 정의.
 * 토크 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class TalkListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->builder($filters)
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();
    }

    public function chunkForExport(array $filters, int $chunkSize, callable $callback): bool
    {
        return $this->builder($filters)->chunk($chunkSize, $callback);
    }

    /**
     * @param  array<int, int>  $talkIds
     * @return Collection<int, Collection<int, TalkComment>>
     */
    public function commentsForTalkIds(array $talkIds, int $limitPerTalk = 5): Collection
    {
        $talkIds = collect($talkIds)
            ->map(static fn (int|string $talkId): int => (int) $talkId)
            ->filter(static fn (int $talkId): bool => $talkId > 0)
            ->unique()
            ->values()
            ->all();

        if ($talkIds === []) {
            return collect();
        }

        $rankedCommentIds = TalkComment::query()
            ->select(['id', 'talk_id'])
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY talk_id ORDER BY id ASC) AS comment_rank')
            ->whereIn('talk_id', $talkIds);

        $commentIds = DB::query()
            ->fromSub($rankedCommentIds, 'ranked_talk_comments')
            ->where('comment_rank', '<=', $limitPerTalk)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($commentIds === []) {
            return collect();
        }

        return TalkComment::query()
            ->select(['id', 'talk_id', 'author_id', 'content'])
            ->whereIn('id', $commentIds)
            ->with('author:id,name,nickname,email')
            ->orderBy('talk_id')
            ->orderBy('id')
            ->get()
            ->groupBy('talk_id')
            ->map(static fn (Collection $comments): Collection => $comments->values());
    }

    private function builder(array $filters): Builder
    {
        $builder = Talk::query()
            ->select([
                'id',
                'author_id',
                'title',
                'content',
                'status',
                'post_status',
                'is_pinned',
                'pinned_order',
                'view_count',
                'comment_count',
                'like_count',
                'save_count',
                'created_at',
                'updated_at',
            ])
            ->with([
                'author:id,name,nickname,email',
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
                    ->orWhere('content', 'like', "%{$q}%")
                    ->orWhereHas('author', fn ($authorQuery) => $authorQuery
                        ->where('nickname', 'like', "%{$q}%"));
            });
        }

        if (is_array($filters['status'] ?? null) && $filters['status'] !== []) {
            $builder->whereIn('status', $filters['status']);
        }

        if (is_array($filters['post_status'] ?? null) && $filters['post_status'] !== []) {
            $builder->whereIn('post_status', $filters['post_status']);
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
                    'categories',
                    fn ($query) => $query
                        ->where('categories.domain', Talk::CATEGORY_DOMAIN)
                        ->whereIn('categories.id', $normalizedCategoryIds)
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

        return $builder;
    }
}
