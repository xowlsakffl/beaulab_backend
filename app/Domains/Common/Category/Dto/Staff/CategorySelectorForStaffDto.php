<?php

namespace App\Domains\Common\Category\Dto\Staff;

use App\Domains\Common\Category\Models\Category;

/**
 * CategorySelectorForStaffDto DTO.
 */
final readonly class CategorySelectorForStaffDto
{
    public function __construct(
        public int $id,
        public string $domain,
        public ?int $parentId,
        public int $depth,
        public string $name,
        public string $code,
        public ?string $fullPath,
        public string $status,
        public bool $hasChildren,
    ) {}

    public static function fromModel(Category $category): self
    {
        return new self(
            id: (int) $category->id,
            domain: (string) $category->domain,
            parentId: $category->parent_id !== null ? (int) $category->parent_id : null,
            depth: (int) $category->depth,
            name: (string) $category->name,
            code: (string) $category->code,
            fullPath: $category->full_path,
            status: (string) $category->status,
            hasChildren: (bool) $category->getAttribute('has_children'),
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
            'status' => $this->status,
            'has_children' => $this->hasChildren,
        ];

        return $data;
    }
}
