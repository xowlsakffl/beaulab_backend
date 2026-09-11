<?php

namespace App\Domains\AccountHospital\Dto\Hospital;

use App\Domains\AccountHospital\Models\AccountHospital;

/**
 * AccountHospitalForAccountHospitalDto DTO.
 */
final readonly class AccountHospitalForAccountHospitalDto
{
    public function __construct(
        public int $id,
        public string $nickname,
        public ?string $email,
        public string $status,
        public ?int $hospitalId,
        public ?string $lastLoginAt,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromModel(AccountHospital $hospital): self
    {
        return new self(
            id: $hospital->id,
            nickname: $hospital->nickname,
            email: $hospital->verifiedEmail(),
            status: $hospital->status,
            hospitalId: $hospital->hospital_id,
            lastLoginAt: $hospital->last_login_at?->toISOString(),
            createdAt: $hospital->created_at?->toISOString() ?? '',
            updatedAt: $hospital->updated_at?->toISOString() ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'email_verified' => $this->email !== null,
            'status' => $this->status,
            'hospital_id' => $this->hospitalId,
            'last_login_at' => $this->lastLoginAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
