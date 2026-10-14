<?php

namespace App\Domains\HospitalEvent\Queries\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use Illuminate\Database\Eloquent\Collection;

final class HospitalEventCategoryFilterOptionsForStaffQuery
{
    /**
     * @return Collection<int, Category>
     */
    public function majorCategories(): Collection
    {
        return Category::query()
            ->where('categories.domain', Category::DOMAIN_HOSPITAL_MEDICAL)
            ->whereNull('categories.parent_id')
            ->where('categories.status', Category::STATUS_ACTIVE)
            ->select([
                'categories.id',
                'categories.domain',
                'categories.group_code',
                'categories.parent_id',
                'categories.depth',
                'categories.name',
                'categories.code',
                'categories.full_path',
                'categories.sort_order',
                'categories.status',
            ])
            ->selectRaw($this->hasChildrenSelect())
            ->orderByRaw($this->usageOrderSql())
            ->orderBy('categories.sort_order')
            ->orderBy('categories.id')
            ->get()->each(fn ($row) => $row->setAttribute('filter_usage', CategoryUsage::eventUsageForGroup($row->group_code)))
            ->unique('id')
            ->values();
    }

    /**
     * @param  array<int, int>  $parentIds
     * @return Collection<int, Category>
     */
    public function middleCategoriesByParents(array $parentIds): Collection
    {
        if ($parentIds === []) {
            return new Collection;
        }

        return Category::query()
            ->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)
            ->where('status', Category::STATUS_ACTIVE)
            ->whereIn('parent_id', array_values($parentIds))
            ->whereIn('id', app(\App\Domains\Common\Category\Support\CategoryUsageAvailability::class)->idsForUsages(CategoryUsage::hospitalEventUsages()))
            ->select([
                'id',
                'domain',
                'group_code',
                'parent_id',
                'depth',
                'name',
                'code',
                'full_path',
                'sort_order',
                'status',
            ])
            ->selectRaw($this->hasChildrenSelect())
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function hasChildrenSelect(): string
    {
        return 'EXISTS(
            SELECT 1
            FROM categories as c_children
            WHERE c_children.domain = categories.domain
              AND c_children.parent_id = categories.id
            LIMIT 1
        ) as has_children';
    }

    private function usageOrderSql(): string
    {
        return "CASE categories.group_code
            WHEN 'SURGERY' THEN 1
            WHEN 'TREATMENT' THEN 2
            WHEN 'PROMOTION' THEN 3
            ELSE 99
        END";
    }
}
