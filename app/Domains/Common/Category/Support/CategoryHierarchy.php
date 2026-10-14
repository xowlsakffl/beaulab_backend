<?php

namespace App\Domains\Common\Category\Support;

use App\Domains\Common\Category\Models\Category;
use Illuminate\Support\Collection;

final class CategoryHierarchy
{
    public static function rootIds(Collection $categories): array
    {
        $roots = [];
        foreach ($categories->sortBy('depth') as $category) {
            $roots[$category->id] = $category->parent_id ? ($roots[$category->parent_id] ?? null) : $category->id;
        }

        return $roots;
    }

    public static function descendants(Category $category): array
    {
        return self::selectedAndDescendants($category->domain, [$category->id]);
    }

    public static function selectedAndDescendants(string $domain, array $selectedIds): array
    {
        $rows = Category::where('domain', $domain)->get(['id', 'parent_id', 'depth', 'domain', 'group_code']);

        return self::expandSelection($rows, $selectedIds);
    }

    public static function expandSelection(Collection $rows, array $selectedIds): array
    {
        $byId = $rows->keyBy('id');
        $ids = array_fill_keys(array_intersect($rows->pluck('id')->all(), $selectedIds), true);
        foreach ($rows->sortBy('depth') as $row) {
            $parent = $byId->get($row->parent_id);
            if ($parent && isset($ids[$parent->id]) && $parent->domain === $row->domain && $parent->group_code === $row->group_code) {
                $ids[$row->id] = true;
            }
        }

        return array_keys($ids);
    }
}
