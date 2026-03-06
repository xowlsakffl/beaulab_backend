<?php

namespace App\Domains\Common\Actions\Category\Staff;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Media\Media;
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
            'middle_count' => (int) ($category->middle_count ?? 0),
            'small_count' => (int) ($category->small_count ?? 0),
            'icon' => $this->formatMedia($category->relationLoaded('iconMedia') ? $category->iconMedia : null),
            'created_at' => optional($category->created_at)?->toISOString(),
            'updated_at' => optional($category->updated_at)?->toISOString(),
            'parent' => $category->relationLoaded('parent') && $category->parent
                ? $this->toSimpleArray($category->parent)
                : null,
            'children' => $category->relationLoaded('children')
                ? $category->children->map(fn (Category $child) => $this->toSimpleArray($child))->values()->all()
                : [],
        ];
    }

    private function toSimpleArray(Category $category): array
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
            'icon' => $this->formatMedia($category->relationLoaded('iconMedia') ? $category->iconMedia : null),
            'created_at' => optional($category->created_at)?->toISOString(),
            'updated_at' => optional($category->updated_at)?->toISOString(),
            'children' => $category->relationLoaded('children')
                ? $category->children->map(fn (Category $child) => $this->toSimpleArray($child))->values()->all()
                : [],
        ];
    }

    private function formatMedia(?Media $media): ?array
    {
        if (! $media) {
            return null;
        }

        return [
            'id' => (int) $media->id,
            'collection' => (string) $media->collection,
            'disk' => (string) $media->disk,
            'path' => (string) $media->path,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'width' => $media->width,
            'height' => $media->height,
            'sort_order' => (int) $media->sort_order,
            'is_primary' => (bool) $media->is_primary,
            'metadata' => $media->metadata,
            'created_at' => optional($media->created_at)?->toISOString(),
            'updated_at' => optional($media->updated_at)?->toISOString(),
        ];
    }
}
