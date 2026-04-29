<?php

namespace App\Domains\AccountStaff\Dto\Staff;

use App\Domains\AccountStaff\Models\AccountStaff;

/**
 * AccountStaffForStaffDto DTO.
 */
final readonly class AccountStaffForStaffDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $nickname,
        public string $email,
        public string $status,
        public ?string $lastLoginAt,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromModel(AccountStaff $staff): self
    {
        return new self(
            id: $staff->id,
            name: $staff->name,
            nickname: $staff->nickname,
            email: $staff->email,
            status: $staff->status,
            lastLoginAt: $staff->last_login_at?->toISOString(),
            createdAt: $staff->created_at?->toISOString() ?? '',
            updatedAt: $staff->updated_at?->toISOString() ?? '',
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
            'last_login_at' => $this->lastLoginAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
