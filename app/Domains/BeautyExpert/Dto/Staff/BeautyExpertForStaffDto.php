<?php

namespace App\Domains\BeautyExpert\Dto\Staff;

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
        );
    }

    public function toArray(): array
    {
        return [
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
    }
}
