<?php

namespace App\Domains\Common\Queries\Category\Staff;

use App\Domains\Common\Models\Category\Category;

final class CategoryGetForStaffQuery
{
    /**
     * @param array<int, string> $include
     */
    public function get(Category $category, array $include = []): Category
    {
        $builder = Category::query()->select([
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
        ])->with('iconMedia');

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

        return $builder->findOrFail($category->id);
    }
}
