<?php

namespace App\Domains\Common\Category\Queries;

use App\Domains\Common\Category\Models\Category;

final class CategoryTreeWriteQuery
{
    public function lockPath(int $id): Category
    {
        $ids = [];
        $node = Category::query()->findOrFail($id);
        while ($node !== null) {
            if (in_array((int) $node->id, $ids, true)) {
                throw new \LogicException('카테고리 계층에 순환 참조가 있습니다.');
            }
            $ids[] = (int) $node->id;
            $node = $node->parent_id === null ? null : Category::query()->findOrFail($node->parent_id);
        }

        // 부모 변경과 하위 생성·수정을 같은 가지의 상위 행부터 직렬화한다.
        foreach (array_reverse($ids) as $ancestorId) {
            $locked = Category::query()->lockForUpdate()->findOrFail($ancestorId);
        }

        return $locked;
    }

    public function syncDescendants(Category $parent): void
    {
        $children = Category::query()->where('parent_id', $parent->id)
            ->orderBy('id')->lockForUpdate()->get();

        foreach ($children as $child) {
            $child->forceFill([
                'full_path' => ($parent->full_path ?: $parent->name).' > '.$child->name,
                'group_code' => $parent->group_code,
            ])->save();
            $this->syncDescendants($child);
        }
    }
}
