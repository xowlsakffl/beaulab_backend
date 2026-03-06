<?php

namespace App\Domains\Common\Queries\Category\Staff;

use App\Domains\Common\Models\Category\Category;

final class CategoryUpdateForStaffQuery
{
    public function update(Category $category, array $data): Category
    {
        $category->fill($data);

        if ($category->isDirty()) {
            $category->save();
        }

        return $category->fresh();
    }
}

