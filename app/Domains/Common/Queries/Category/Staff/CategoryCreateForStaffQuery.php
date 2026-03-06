<?php

namespace App\Domains\Common\Queries\Category\Staff;

use App\Domains\Common\Models\Category\Category;

final class CategoryCreateForStaffQuery
{
    public function create(array $data): Category
    {
        return Category::create($data);
    }
}
