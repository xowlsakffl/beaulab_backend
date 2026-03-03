<?php

namespace App\Domains\Beauty\Dto\Auth;

use App\Domains\Beauty\Models\AccountBeauty;

final readonly class ProfileForBeautyDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $nickname,
        public string $email,
        public string $status,
        public ?string $beautyType,
        public ?int $hospitalId,
        public ?int $beautyId,
        public ?string $lastLoginAt,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromModel(AccountBeauty $beauty): self
    {
        return new self(
            id: $beauty->id,
            name: $beauty->name,
            nickname: $beauty->nickname,
            email: $beauty->email,
            status: $beauty->status,
            beautyType: $beauty->beauty_type,
            hospitalId: $beauty->hospital_id,
            beautyId: $beauty->beauty_id,
            lastLoginAt: $beauty->last_login_at?->toISOString(),
            createdAt: $beauty->created_at?->toISOString() ?? '',
            updatedAt: $beauty->updated_at?->toISOString() ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'status' => $this->status,
            'beauty_type' => $this->beautyType,
            'hospital_id' => $this->hospitalId,
            'beauty_id' => $this->beautyId,
            'last_login_at' => $this->lastLoginAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
