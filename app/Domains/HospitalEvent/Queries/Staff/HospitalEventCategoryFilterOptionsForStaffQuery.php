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
            ->join('category_usages', 'category_usages.category_id', '=', 'categories.id')
            ->where('categories.domain', Category::DOMAIN_HOSPITAL_MEDICAL)
            ->where('categories.status', Category::STATUS_ACTIVE)
            ->where('category_usages.status', CategoryUsage::STATUS_ACTIVE)
            ->whereIn('category_usages.usage', CategoryUsage::hospitalEventUsages())
            ->select([
                'categories.id',
                'categories.domain',
                'categories.parent_id',
                'categories.depth',
                'categories.name',
                'categories.code',
                'categories.full_path',
                'categories.sort_order',
                'categories.status',
                'category_usages.usage as filter_usage',
                'category_usages.sort_order as usage_sort_order',
            ])
            ->selectRaw($this->hasChildrenSelect())
            ->orderByRaw($this->usageOrderSql())
            ->orderBy('category_usages.sort_order')
            ->orderBy('categories.id')
            ->get()
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
        return "CASE category_usages.usage
            WHEN '".CategoryUsage::USAGE_HOSPITAL_EVENT_SURGERY."' THEN 1
            WHEN '".CategoryUsage::USAGE_HOSPITAL_EVENT_TREATMENT."' THEN 2
            WHEN '".CategoryUsage::USAGE_HOSPITAL_EVENT_PROMOTION."' THEN 3
            ELSE 99
        END";
    }
}
