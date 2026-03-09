<?php

namespace App\Domains\Beauty\Dto\Staff;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Beauty\Models\Beauty;

final readonly class BeautyForStaffDto
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $address,
        public ?string $tel,
        public int $viewCount,
        public string $allowStatus,
        public string $status,
        public string $createdAt,
        public string $updatedAt,
        public ?array $categories = null,
    ) {}

    public static function fromModel(Beauty $beauty): self
    {
        return new self(
            id: $beauty->id,
            name: $beauty->name,
            address: $beauty->address,
            tel: $beauty->tel,
            viewCount: (int) $beauty->view_count,
            allowStatus: $beauty->allow_status,
            status: $beauty->status,
            createdAt: $beauty->created_at?->toISOString() ?? '',
            updatedAt: $beauty->updated_at?->toISOString() ?? '',
            categories: $beauty->relationLoaded('categories')
                ? $beauty->categories
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
            'id'           => $this->id,
            'name'         => $this->name,
            'address'      => $this->address,
            'tel'          => $this->tel,
            'view_count'   => $this->viewCount,
            'allow_status' => $this->allowStatus,
            'status'       => $this->status,
            'created_at'   => $this->createdAt,
            'updated_at'   => $this->updatedAt,
        ];

        if ($this->categories !== null) {
            $data['categories'] = $this->categories;
        }

        return $data;
    }
}
