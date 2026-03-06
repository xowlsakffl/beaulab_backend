<?php

namespace App\Domains\Common\Queries\Category\Staff;

use App\Domains\Common\Models\Category\Category;

final class CategoryDeleteForStaffQuery
{
    public function delete(Category $category): void
    {
        $category->delete();
    }
}

