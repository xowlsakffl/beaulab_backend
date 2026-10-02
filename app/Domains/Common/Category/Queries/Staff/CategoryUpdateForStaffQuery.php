<?php

namespace App\Domains\Common\Category\Queries\Staff;

use App\Domains\Common\Category\Models\Category;

/**
 * CategoryUpdateForStaffQuery 역할 정의.
 * 공통 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class CategoryUpdateForStaffQuery
{
    public function parent(Category $category): ?Category
    {
        return $category->parent()->lockForUpdate()->first();
    }

    public function existsSiblingName(Category $category, string $name): bool
    {
        return Category::query()
            ->domain((string) $category->domain)
            ->where('parent_id', $category->parent_id)
            ->where('name', $name)
            ->whereKeyNot($category->id)
            ->exists();
    }

    public function update(Category $category, array $data): Category
    {
        $category->fill($data);

        if ($category->isDirty()) {
            $category->save();
        }

        return $category->fresh();
    }
}
