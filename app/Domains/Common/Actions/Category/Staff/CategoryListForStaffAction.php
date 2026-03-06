<?php

namespace App\Domains\Common\Actions\Category\Staff;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Queries\Category\Staff\CategoryListForStaffQuery;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class CategoryListForStaffAction
{
    public function __construct(
        private readonly CategoryListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', Category::class);

        Log::info('카테고리 목록 조회', [
            'filters' => $filters,
        ]);

        $paginator = $this->query->paginate($filters);

        $items = collect($paginator->items())
            ->map(fn (Category $category) => $this->toArray($category))
            ->values()
            ->all();

        return [
            'items' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
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
        ];
    }
}

