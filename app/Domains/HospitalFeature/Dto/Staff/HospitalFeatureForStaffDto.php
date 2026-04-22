<?php

namespace App\Domains\HospitalFeature\Dto\Staff;

use App\Domains\HospitalFeature\Models\HospitalFeature;

/**
 * HospitalFeatureForStaffDto DTO.
 */
final readonly class HospitalFeatureForStaffDto
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public int $sortOrder,
        public string $status,
    ) {}

    public static function fromModel(HospitalFeature $feature): self
    {
        return new self(
            id: (int) $feature->id,
            code: (string) $feature->code,
            name: (string) $feature->name,
            sortOrder: (int) $feature->sort_order,
            status: (string) $feature->status,
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'sort_order' => $this->sortOrder,
            'status' => $this->status,
        ];

        return $data;
    }
}
