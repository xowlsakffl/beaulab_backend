<?php

namespace App\Domains\Common\Queries\Category\Staff;

use App\Domains\Common\Models\Category\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class CategorySelectorListForStaffQuery
{
    public function get(array $filters): Collection
    {
        $builder = $this->buildQuery($filters);
        $q = $filters['q'] ?? null;
        $limit = (int) ($filters['per_page'] ?? 50);

        if ($q) {
            $builder->limit($limit);
        }

        return $builder->get();
    }

    private function buildQuery(array $filters): Builder
    {
        $domain = (string) $filters['domain'];
        $q = $filters['q'] ?? null;
        $status = $filters['status'] ?? null;
        $parentId = $filters['parent_id'] ?? null;
        $depth = $filters['depth'] ?? null;
        $isMenuVisible = $filters['is_menu_visible'] ?? null;
        $sort = $filters['sort'] ?? 'sort_order';
        $direction = $filters['direction'] ?? 'asc';

        $builder = Category::query()
            ->domain($domain)
            ->select([
                'id',
                'domain',
                'parent_id',
                'depth',
                'name',
                'full_path',
                'sort_order',
                'status',
            ]);

        $builder->selectRaw(
            'EXISTS(
                SELECT 1
                FROM categories as c_children
                WHERE c_children.domain = categories.domain
                  AND c_children.parent_id = categories.id
                LIMIT 1
            ) as has_children'
        );

        if ($q) {
            $builder->where(function ($w) use ($q): void {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('full_path', 'like', "%{$q}%");
            });
        }

        if ($parentId !== null) {
            $builder->where('parent_id', (int) $parentId);
        }

        if ($depth !== null) {
            $builder->where('depth', (int) $depth);
        } elseif ($parentId === null && ! $q) {
            $builder->whereNull('parent_id');
        }

        if (is_array($status) && $status !== []) {
            $builder->whereIn('status', $status);
        }

        if ($isMenuVisible !== null) {
            $builder->where('is_menu_visible', (bool) $isMenuVisible);
        }

        $builder->orderBy($sort, $direction);

        if ($sort !== 'id') {
            $builder->orderBy('id');
        }

        return $builder;
    }
}
