<?php

namespace App\Domains\Common\Actions\Category\Staff;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Media\Media;
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
            'icon' => $this->formatMedia($category->relationLoaded('iconMedia') ? $category->iconMedia : null),
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
                    'icon' => $this->formatMedia($category->parent->relationLoaded('iconMedia') ? $category->parent->iconMedia : null),
                    'created_at' => optional($category->parent->created_at)?->toISOString(),
                    'updated_at' => optional($category->parent->updated_at)?->toISOString(),
                ]
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
