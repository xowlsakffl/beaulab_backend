<?php

namespace App\Domains\Hospital\Dto\Admin;

use App\Domains\Hospital\Models\Hospital;

final readonly class HospitalListForStaffDto
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
    ) {}

    public static function fromModel(Hospital $hospital): self
    {
        return new self(
            id: $hospital->id,
            name: $hospital->name,
            address: $hospital->address,
            tel: $hospital->tel,
            viewCount: (int) $hospital->view_count,
            allowStatus: $hospital->allow_status,
            status: $hospital->status,
            createdAt: $hospital->created_at?->toISOString() ?? '',
            updatedAt: $hospital->updated_at?->toISOString() ?? '',
        );
    }

    public function toArray(): array
    {
        return [
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
    }
}
