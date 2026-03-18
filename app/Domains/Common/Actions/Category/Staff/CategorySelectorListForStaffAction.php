<?php

namespace App\Domains\Common\Actions\Category\Staff;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Queries\Category\Staff\CategorySelectorListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class CategorySelectorListForStaffAction
{
    public function __construct(
        private readonly CategorySelectorListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', Category::class);

        $items = $this->query->get($filters)
            ->map(fn (Category $category) => $this->toArray($category))
            ->values()
            ->all();

        return [
            'items' => $items,
            'meta' => null,
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
            'full_path' => $category->full_path,
            'status' => (string) $category->status,
            'has_children' => (bool) $category->getAttribute('has_children'),
        ];
    }
}
