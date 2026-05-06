<?php

namespace App\Domains\Common\Category\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;

/**
 * CategoryForStaffDto DTO.
 */
final readonly class CategoryForStaffDto
{
    public function __construct(
        public int $id,
        public string $domain,
        public ?int $parentId,
        public int $depth,
        public string $name,
        public ?string $code,
        public ?string $fullPath,
        public int $sortOrder,
        public string $status,
        public bool $isMenuVisible,
        public bool $hasChildren,
        public ?array $icon,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?array $parent = null,
        public ?array $children = null,
        public ?int $middleCount = null,
        public ?int $smallCount = null,
    ) {}

    public static function fromModel(Category $category): self
    {
        return new self(
            id: (int) $category->id,
            domain: (string) $category->domain,
            parentId: $category->parent_id !== null ? (int) $category->parent_id : null,
            depth: (int) $category->depth,
            name: (string) $category->name,
            code: $category->code,
            fullPath: $category->full_path,
            sortOrder: (int) $category->sort_order,
            status: (string) $category->status,
            isMenuVisible: (bool) $category->is_menu_visible,
            hasChildren: self::hasChildren($category),
            icon: self::icon($category),
            createdAt: $category->created_at?->toISOString(),
            updatedAt: $category->updated_at?->toISOString(),
            parent: $category->relationLoaded('parent') && $category->parent
                ? self::simple($category->parent)
                : null,
            children: $category->relationLoaded('children')
                ? $category->children->map(fn (Category $child): array => self::simple($child))->values()->all()
                : null,
            middleCount: array_key_exists('middle_count', $category->getAttributes())
                ? (int) ($category->middle_count ?? 0)
                : null,
            smallCount: array_key_exists('small_count', $category->getAttributes())
                ? (int) ($category->small_count ?? 0)
                : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'domain' => $this->domain,
            'parent_id' => $this->parentId,
            'depth' => $this->depth,
            'name' => $this->name,
            'code' => $this->code,
            'full_path' => $this->fullPath,
            'sort_order' => $this->sortOrder,
            'status' => $this->status,
            'is_menu_visible' => $this->isMenuVisible,
            'has_children' => $this->hasChildren,
            'icon' => $this->icon,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        if ($this->parent !== null) {
            $data['parent'] = $this->parent;
        }

        if ($this->children !== null) {
            $data['children'] = $this->children;
        }

        if ($this->middleCount !== null) {
            $data['middle_count'] = $this->middleCount;
        }

        if ($this->smallCount !== null) {
            $data['small_count'] = $this->smallCount;
        }

        return $data;
    }

    private static function simple(Category $category): array
    {
        $data = [
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
            'has_children' => self::hasChildren($category),
            'icon' => self::icon($category),
            'created_at' => $category->created_at?->toISOString(),
            'updated_at' => $category->updated_at?->toISOString(),
        ];

        if ($category->relationLoaded('children')) {
            $data['children'] = $category->children
                ->map(fn (Category $child): array => self::simple($child))
                ->values()
                ->all();
        }

        return $data;
    }

    private static function icon(Category $category): ?array
    {
        if (! $category->relationLoaded('iconMedia')) {
            return null;
        }

        return self::media($category->iconMedia);
    }

    private static function media(?Media $media): ?array
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
            'created_at' => $media->created_at?->toISOString(),
            'updated_at' => $media->updated_at?->toISOString(),
        ];
    }

    private static function hasChildren(Category $category): bool
    {
        if (array_key_exists('has_children', $category->getAttributes())) {
            return (bool) $category->getAttribute('has_children');
        }

        if ($category->relationLoaded('children')) {
            return $category->children->isNotEmpty();
        }

        $depth = (int) $category->depth;

        if ($depth === 1) {
            return (int) ($category->middle_count ?? 0) > 0;
        }

        if ($depth === 2) {
            return (int) ($category->small_count ?? 0) > 0;
        }

        return false;
    }
}
