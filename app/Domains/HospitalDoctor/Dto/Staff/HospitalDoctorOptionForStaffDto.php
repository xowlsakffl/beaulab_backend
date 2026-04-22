<?php

namespace App\Domains\HospitalDoctor\Dto\Staff;

use App\Domains\HospitalDoctor\Models\HospitalDoctor;

/**
 * HospitalDoctorOptionForStaffDto DTO.
 */
final readonly class HospitalDoctorOptionForStaffDto
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $position,
    ) {}

    public static function fromModel(HospitalDoctor $doctor): self
    {
        return new self(
            id: (int) $doctor->id,
            name: (string) $doctor->name,
            position: $doctor->position,
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'position' => $this->position,
        ];

        return $data;
    }
}
