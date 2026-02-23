<?php

namespace App\Domains\Beauty\Dto\Staff;

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
