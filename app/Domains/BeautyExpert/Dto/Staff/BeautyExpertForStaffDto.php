<?php

namespace App\Domains\BeautyExpert\Dto\Staff;

use App\Domains\Common\Models\Category\Category;
use App\Domains\BeautyExpert\Models\BeautyExpert;

final readonly class BeautyExpertForStaffDto
{
    public function __construct(
        public int $id,
        public int $beautyId,
        public string $name,
        public ?string $position,
        public int $sortOrder,
        public string $allowStatus,
        public string $status,
        public string $createdAt,
        public string $updatedAt,
        public ?array $categories = null,
    ) {}

    public static function fromModel(BeautyExpert $expert): self
    {
        return new self(
            id: $expert->id,
            beautyId: $expert->beauty_id,
            name: $expert->name,
            position: $expert->position,
            sortOrder: (int) $expert->sort_order,
            allowStatus: $expert->allow_status,
            status: $expert->status,
            createdAt: $expert->created_at?->toISOString() ?? '',
            updatedAt: $expert->updated_at?->toISOString() ?? '',
            categories: $expert->relationLoaded('categories')
                ? $expert->categories
                    ->map(fn (Category $category): array => [
                        'name' => (string) $category->name,
                    ])
                    ->values()
                    ->all()
                : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'beauty_id' => $this->beautyId,
            'name' => $this->name,
            'position' => $this->position,
            'sort_order' => $this->sortOrder,
            'allow_status' => $this->allowStatus,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        if ($this->categories !== null) {
            $data['categories'] = $this->categories;
        }

        return $data;
    }
}
