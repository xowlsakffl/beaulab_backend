<?php

namespace App\Domains\Hospital\Dto\Auth;

use App\Domains\Hospital\Models\AccountHospital;

final readonly class AuthForHospitalDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $nickname,
        public string $email,
        public string $status,
        public ?string $hospitalType,
        public ?int $hospitalId,
        public ?int $beautyId,
        public ?string $lastLoginAt,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromModel(AccountHospital $hospital): self
    {
        return new self(
            id: $hospital->id,
            name: $hospital->name,
            nickname: $hospital->nickname,
            email: $hospital->email,
            status: $hospital->status,
            hospitalType: $hospital->hospital_type,
            hospitalId: $hospital->hospital_id,
            beautyId: $hospital->beauty_id,
            lastLoginAt: $hospital->last_login_at?->toISOString(),
            createdAt: $hospital->created_at?->toISOString() ?? '',
            updatedAt: $hospital->updated_at?->toISOString() ?? '',
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
            'hospital_type' => $this->hospitalType,
            'hospital_id' => $this->hospitalId,
            'beauty_id' => $this->beautyId,
            'last_login_at' => $this->lastLoginAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
