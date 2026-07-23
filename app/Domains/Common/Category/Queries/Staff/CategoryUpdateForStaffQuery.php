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
        return $category->parent()->first();
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

    public function syncDescendantPaths(string $domain, string $oldPrefix, string $newPrefix): void
    {
        $descendants = Category::query()
            ->domain($domain)
            ->where('full_path', 'like', $oldPrefix.' > %')
            ->orderBy('depth')
            ->get();

        foreach ($descendants as $descendant) {
            $currentPath = (string) ($descendant->full_path ?? '');

            $nextPath = preg_replace(
                '/^'.preg_quote($oldPrefix, '/').'/',
                $newPrefix,
                $currentPath,
                1
            );

            if ($nextPath === null || $nextPath === $currentPath) {
                continue;
            }

            $descendant->forceFill([
                'full_path' => $nextPath,
            ])->save();
        }
    }

    public function syncDescendantGroupCode(string $domain, string $fullPathPrefix, ?string $groupCode): void
    {
        Category::query()
            ->domain($domain)
            ->where('full_path', 'like', $fullPathPrefix.' > %')
            ->update(['group_code' => $groupCode]);
    }
}
