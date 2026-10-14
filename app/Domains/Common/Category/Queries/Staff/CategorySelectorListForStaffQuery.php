<?php

namespace App\Domains\Common\Category\Queries\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Support\CategoryUsageAvailability;
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

        $items = $builder->get();
        if (! empty($filters['usage'])) {
            $available = app(CategoryUsageAvailability::class)->ids($filters['usage'], (bool) ($filters['is_menu_visible'] ?? false));
            $parents = Category::query()->whereIn('id', $available)->whereNotNull('parent_id')->pluck('parent_id')->all();
            foreach ($items as $category) {
                $category->setAttribute('presentation_usage', $filters['usage']);
                $category->setAttribute('has_children', in_array($category->id, $parents, true));
            }
        }

        return $items;
    }

    private function buildQuery(array $filters): Builder
    {
        $domain = ! empty($filters['usage']) ? \App\Domains\Common\Category\Models\CategoryUsage::domainFor($filters['usage']) : (string) $filters['domain'];
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

        if ($usage) {
            $builder->whereIn('categories.id', app(CategoryUsageAvailability::class)->ids($usage, (bool) $isMenuVisible));
            if ($isMenuVisible !== null && ! $isMenuVisible) {
                $builder->whereNotIn('categories.id', app(CategoryUsageAvailability::class)->ids($usage, true));
            }
        } elseif ($status === [Category::STATUS_ACTIVE] || $isMenuVisible) {
            $builder->whereIn('categories.id', app(CategoryUsageAvailability::class)->activeIdsForDomain($domain, (bool) $isMenuVisible));
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
            $builder->where(function ($w) use ($q, $usage): void {
                $w->where('categories.name', 'like', "%{$q}%")
                    ->orWhere('categories.code', 'like', "%{$q}%")
                    ->orWhere('categories.full_path', 'like', "%{$q}%");
                if ($usage) {
                    $w->orWhereIn('categories.id', app(CategoryUsageAvailability::class)->searchIds($usage, $q));
                }
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
        } elseif ($parentId === null && ! $parentCode && ! $q) {
            $builder->whereNull('categories.parent_id');
        }

        if ($groupCode !== null) {
            $builder->where('categories.group_code', (string) $groupCode);
        }

        if (is_array($status) && $status !== []) {
            $builder->whereIn('categories.status', $status);
        }

        if ($isMenuVisible !== null && ! $usage) {
            $builder->where('categories.is_menu_visible', (bool) $isMenuVisible);
        }

        if ($usage && ! $q && $sort === 'sort_order') {
            $builder->orderBy('categories.sort_order', $direction);
        } else {
            $builder->orderBy("categories.{$sort}", $direction);
        }

        if ($sort !== 'id') {
            $builder->orderBy('categories.id');
        }

        return $builder;
    }
}
