<?php

namespace App\Domains\Doctor\Dto\Staff;

use App\Domains\Doctor\Models\Doctor;

final readonly class DoctorForStaffDto
{
    public function __construct(
        public int $id,
        public int $hospitalId,
        public string $name,
        public ?string $position,
        public bool $isSpecialist,
        public int $sortOrder,
        public string $allowStatus,
        public string $status,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromModel(Doctor $doctor): self
    {
        return new self(
            id: $doctor->id,
            hospitalId: $doctor->hospital_id,
            name: $doctor->name,
            position: $doctor->position,
            isSpecialist: (bool) $doctor->is_specialist,
            sortOrder: (int) $doctor->sort_order,
            allowStatus: $doctor->allow_status,
            status: $doctor->status,
            createdAt: $doctor->created_at?->toISOString() ?? '',
            updatedAt: $doctor->updated_at?->toISOString() ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'hospital_id' => $this->hospitalId,
            'name' => $this->name,
            'position' => $this->position,
            'is_specialist' => $this->isSpecialist,
            'sort_order' => $this->sortOrder,
            'allow_status' => $this->allowStatus,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
