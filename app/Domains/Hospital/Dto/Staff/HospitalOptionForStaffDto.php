<?php

namespace App\Domains\Hospital\Dto\Staff;

use App\Domains\Hospital\Models\Hospital;

/**
 * HospitalOptionForStaffDto DTO.
 */
final readonly class HospitalOptionForStaffDto
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $businessNumber,
    ) {}

    public static function fromModel(Hospital $hospital): self
    {
        return new self(
            id: (int) $hospital->id,
            name: (string) $hospital->name,
            businessNumber: $hospital->businessRegistration?->business_number,
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'business_number' => $this->businessNumber,
        ];

        return $data;
    }
}
