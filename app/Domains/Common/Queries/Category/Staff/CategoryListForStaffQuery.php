<?php

namespace App\Domains\Common\Queries\Category\Staff;

use App\Domains\Common\Models\Category\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class CategoryListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 50;

        return $this->buildQuery($filters)
            ->paginate((int) $perPage)
            ->withQueryString();
    }

    private function buildQuery(array $filters): Builder
    {
        $domain = (string) $filters['domain'];
        $q = $filters['q'] ?? null;
        $status = $filters['status'] ?? null;
        $include = $filters['include'] ?? [];
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
                'code',
                'full_path',
                'sort_order',
                'status',
                'is_menu_visible',
                'created_at',
                'updated_at',
            ])
            ->with('iconMedia');

        $builder->selectRaw(
            'EXISTS(
                SELECT 1
                FROM categories as c_children
                WHERE c_children.domain = categories.domain
                  AND c_children.parent_id = categories.id
                LIMIT 1
            ) as has_children'
        );

        $builder->selectSub(
            Category::query()
                ->from('categories as c2')
                ->selectRaw('COUNT(*)')
                ->whereColumn('c2.domain', 'categories.domain')
                ->whereColumn('c2.parent_id', 'categories.id')
                ->where('c2.depth', 2),
            'middle_count',
        );

        $builder->selectSub(
            Category::query()
                ->from('categories as c3')
                ->selectRaw('COUNT(*)')
                ->whereColumn('c3.domain', 'categories.domain')
                ->where('c3.depth', 3)
                ->whereRaw(
                    "c3.full_path LIKE CONCAT(COALESCE(categories.full_path, categories.name), ' > %')"
                ),
            'small_count',
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

        if (is_array($include) && $include !== []) {
            if (in_array('parent', $include, true)) {
                $builder->with([
                    'parent:id,domain,parent_id,depth,name,code,full_path,sort_order,status,is_menu_visible,created_at,updated_at',
                    'parent.iconMedia',
                ]);
            }

            if (in_array('children', $include, true)) {
                $selectColumns = [
                    'id',
                    'domain',
                    'parent_id',
                    'depth',
                    'name',
                    'code',
                    'full_path',
                    'sort_order',
                    'status',
                    'is_menu_visible',
                    'created_at',
                    'updated_at',
                ];

                $builder->with([
                    'children' => fn ($q) => $q
                        ->select($selectColumns)
                        ->orderBy('sort_order')
                        ->orderBy('id'),
                    'children.iconMedia',
                    'children.children' => fn ($q) => $q
                        ->select($selectColumns)
                        ->orderBy('sort_order')
                        ->orderBy('id'),
                    'children.children.iconMedia',
                ]);
            }
        }

        $builder->orderBy($sort, $direction);

        if ($sort !== 'id') {
            $builder->orderBy('id');
        }

        return $builder;
    }
}
