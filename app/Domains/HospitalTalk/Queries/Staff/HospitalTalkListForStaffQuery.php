<?php

namespace App\Domains\HospitalTalk\Queries\Staff;

use App\Domains\HospitalTalk\Models\HospitalTalk;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class HospitalTalkListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $include = $filters['include'] ?? [];

        $builder = HospitalTalk::query()
            ->select([
                'id',
                'author_id',
                'title',
                'status',
                'is_visible',
                'is_pinned',
                'pinned_order',
                'view_count',
                'comment_count',
                'like_count',
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

        if (! empty($filters['author_id'])) {
            $builder->where('author_id', (int) $filters['author_id']);
        }

        if (! empty($filters['category_id'])) {
            $categoryId = (int) $filters['category_id'];
            $builder->whereHas('categories', fn ($query) => $query->where('categories.id', $categoryId));
        }

        $builder->orderBy($filters['sort'] ?? 'id', $filters['direction'] ?? 'desc');

        return $builder->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
    }
}
