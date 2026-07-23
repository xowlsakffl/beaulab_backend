<?php

namespace App\Domains\Common\Category\Queries\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * CategorySelectorListForStaffQuery 역할 정의.
 * 공통 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
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
        $usage = $filters['usage'] ?? null;
        $status = $filters['status'] ?? null;
        $parentId = $filters['parent_id'] ?? null;
        $parentCode = $filters['parent_code'] ?? null;
        $depth = $filters['depth'] ?? null;
        $groupCode = $filters['group_code'] ?? null;
        $isMenuVisible = $filters['is_menu_visible'] ?? null;
        $sort = $filters['sort'] ?? 'sort_order';
        $direction = $filters['direction'] ?? 'asc';

        $builder = Category::query()
            ->domain($domain)
            ->select([
                'categories.id',
                'categories.domain',
                'categories.parent_id',
                'categories.depth',
                'categories.group_code',
                'categories.name',
                'categories.code',
                'categories.full_path',
                'categories.sort_order',
                'categories.status',
            ]);

        if ($usage && $q) {
            $builder->whereExists(function ($exists) use ($usage): void {
                $exists
                    ->selectRaw('1')
                    ->from('category_usages')
                    ->where('category_usages.usage', (string) $usage)
                    ->where('category_usages.status', CategoryUsage::STATUS_ACTIVE)
                    ->where(function ($w): void {
                        $w->whereColumn('category_usages.category_id', 'categories.id')
                            ->orWhereColumn('category_usages.category_id', 'categories.parent_id')
                            ->orWhereExists(function ($parent): void {
                                $parent
                                    ->selectRaw('1')
                                    ->from('categories as c_parent_1')
                                    ->whereColumn('c_parent_1.id', 'categories.parent_id')
                                    ->whereColumn('category_usages.category_id', 'c_parent_1.parent_id');
                            })
                            ->orWhereExists(function ($grandParent): void {
                                $grandParent
                                    ->selectRaw('1')
                                    ->from('categories as c_parent_1')
                                    ->join('categories as c_parent_2', 'c_parent_2.id', '=', 'c_parent_1.parent_id')
                                    ->whereColumn('c_parent_1.id', 'categories.parent_id')
                                    ->whereColumn('category_usages.category_id', 'c_parent_2.parent_id');
                            });
                    });
            });
        } elseif ($usage) {
            $builder
                ->join('category_usages', 'category_usages.category_id', '=', 'categories.id')
                ->where('category_usages.usage', (string) $usage)
                ->where('category_usages.status', CategoryUsage::STATUS_ACTIVE)
                ->addSelect('category_usages.sort_order as usage_sort_order');
        }

        $builder->selectRaw(
            'EXISTS(
                SELECT 1
                FROM categories as c_children
                WHERE c_children.domain = categories.domain
                  AND c_children.parent_id = categories.id
                  AND c_children.status = ?
                LIMIT 1
            ) as has_children',
            [Category::STATUS_ACTIVE]
        );

        if ($q) {
            $builder->where(function ($w) use ($q): void {
                $w->where('categories.name', 'like', "%{$q}%")
                    ->orWhere('categories.code', 'like', "%{$q}%")
                    ->orWhere('categories.full_path', 'like', "%{$q}%");
            });
        }

        if ($parentId !== null) {
            $builder->where('categories.parent_id', (int) $parentId);
        } elseif (is_string($parentCode) && $parentCode !== '') {
            $resolvedParentId = Category::query()
                ->where('domain', $domain)
                ->where('code', $parentCode)
                ->value('id');

            if ($resolvedParentId === null) {
                $builder->whereRaw('1 = 0');
            } else {
                $builder->where('categories.parent_id', (int) $resolvedParentId);
            }
        }

        if ($depth !== null) {
            $builder->where('categories.depth', (int) $depth);
        } elseif ($parentId === null && ! $parentCode && ! $q && ! $usage) {
            $builder->whereNull('categories.parent_id');
        }

        if ($groupCode !== null) {
            $builder->where('categories.group_code', (string) $groupCode);
        }

        if (is_array($status) && $status !== []) {
            $builder->whereIn('categories.status', $status);
        }

        if ($isMenuVisible !== null) {
            $builder->where('categories.is_menu_visible', (bool) $isMenuVisible);
        }

        if ($usage && ! $q && $sort === 'sort_order') {
            $builder->orderBy('category_usages.sort_order', $direction);
        } else {
            $builder->orderBy("categories.{$sort}", $direction);
        }

        if ($sort !== 'id') {
            $builder->orderBy('categories.id');
        }

        return $builder;
    }
}
