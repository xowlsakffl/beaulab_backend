<?php

namespace App\Domains\Common\Actions\Category\Staff;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Queries\Category\Staff\CategoryGetForStaffQuery;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class CategoryGetForStaffAction
{
    public function __construct(
        private readonly CategoryGetForStaffQuery $query,
    ) {}

    /**
     * @param array<int, string> $include
     */
    public function execute(Category $category, array $include = []): array
    {
        Gate::authorize('view', $category);

        Log::info('카테고리 단건 조회', [
            'category_id' => $category->id,
            'include' => $include,
        ]);

        $detail = $this->query->get($category, $include);

        return [
            'category' => $this->toArray($detail),
        ];
    }

    private function toArray(Category $category): array
    {
        return [
            'id' => (int) $category->id,
            'domain' => (string) $category->domain,
            'parent_id' => $category->parent_id !== null ? (int) $category->parent_id : null,
            'depth' => (int) $category->depth,
            'name' => (string) $category->name,
            'code' => $category->code,
            'full_path' => $category->full_path,
            'sort_order' => (int) $category->sort_order,
            'status' => (string) $category->status,
            'is_menu_visible' => (bool) $category->is_menu_visible,
            'created_at' => optional($category->created_at)?->toISOString(),
            'updated_at' => optional($category->updated_at)?->toISOString(),
            'parent' => $category->relationLoaded('parent') && $category->parent
                ? [
                    'id' => (int) $category->parent->id,
                    'domain' => (string) $category->parent->domain,
                    'parent_id' => $category->parent->parent_id !== null ? (int) $category->parent->parent_id : null,
                    'depth' => (int) $category->parent->depth,
                    'name' => (string) $category->parent->name,
                    'code' => $category->parent->code,
                    'full_path' => $category->parent->full_path,
                    'sort_order' => (int) $category->parent->sort_order,
                    'status' => (string) $category->parent->status,
                    'is_menu_visible' => (bool) $category->parent->is_menu_visible,
                    'created_at' => optional($category->parent->created_at)?->toISOString(),
                    'updated_at' => optional($category->parent->updated_at)?->toISOString(),
                ]
                : null,
            'children' => $category->relationLoaded('children')
                ? $category->children->map(fn (Category $child) => [
                    'id' => (int) $child->id,
                    'domain' => (string) $child->domain,
                    'parent_id' => $child->parent_id !== null ? (int) $child->parent_id : null,
                    'depth' => (int) $child->depth,
                    'name' => (string) $child->name,
                    'code' => $child->code,
                    'full_path' => $child->full_path,
                    'sort_order' => (int) $child->sort_order,
                    'status' => (string) $child->status,
                    'is_menu_visible' => (bool) $child->is_menu_visible,
                    'created_at' => optional($child->created_at)?->toISOString(),
                    'updated_at' => optional($child->updated_at)?->toISOString(),
                ])->values()->all()
                : [],
        ];
    }
}

