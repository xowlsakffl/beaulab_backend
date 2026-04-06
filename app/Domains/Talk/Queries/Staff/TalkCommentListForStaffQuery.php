<?php

namespace App\Domains\Talk\Queries\Staff;

use App\Domains\Talk\Models\TalkComment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class TalkCommentListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $include = $filters['include'] ?? [];

        $builder = TalkComment::query()
            ->select([
                'id',
                'talk_id',
                'parent_id',
                'author_id',
                'content',
                'status',
                'is_visible',
                'like_count',
                'created_at',
                'updated_at',
            ])
            ->withCount('mentions');

        if (is_array($include) && in_array('author', $include, true)) {
            $builder->with(['author:id,name,email']);
        }

        if (is_array($include) && in_array('talk', $include, true)) {
            $builder->with(['talk:id,title']);
        }

        if (is_array($include) && in_array('mentions', $include, true)) {
            $builder->with([
                'mentions' => fn ($query) => $query
                    ->select([
                        'id',
                        'talk_comment_id',
                        'mentioned_user_id',
                        'mentioned_by_user_id',
                        'mention_text',
                        'start_offset',
                        'end_offset',
                    ])
                    ->with(['mentionedUser:id,name']),
            ]);
        }

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
            $builder->where('content', 'like', "%{$q}%");
        }

        if (is_array($filters['status'] ?? null) && $filters['status'] !== []) {
            $builder->whereIn('status', $filters['status']);
        }

        if (array_key_exists('is_visible', $filters) && $filters['is_visible'] !== null) {
            $builder->where('is_visible', (bool) $filters['is_visible']);
        }

        if (! empty($filters['author_id'])) {
            $builder->where('author_id', (int) $filters['author_id']);
        }

        $builder->orderBy($filters['sort'] ?? 'id', $filters['direction'] ?? 'desc');

        return $builder->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
    }
}
