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
        ]);

        if (in_array('parent', $include, true)) {
            $builder->with([
                'parent:id,domain,parent_id,depth,name,code,full_path,sort_order,status,is_menu_visible,created_at,updated_at',
            ]);
        }

        if (in_array('children', $include, true)) {
            $builder->with([
                'children' => fn ($q) => $q
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
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ]);
        }

        return $builder->findOrFail($category->id);
    }
}

