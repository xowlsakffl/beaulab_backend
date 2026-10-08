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
        public ?string $email,
        public string $status,
        public ?string $lastLoginAt,
        public string $createdAt,
        public string $updatedAt,
        public ?string $phone,
        public ?string $department,
        public ?string $jobTitle,
        public int $version,
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
            phone: $staff->phone,
            department: $staff->department,
            jobTitle: $staff->job_title,
            version: (int) $staff->lock_version,
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
            'phone' => $this->phone,
            'department' => $this->department,
            'job_title' => $this->jobTitle,
            'version' => $this->version,
        ];
    }
}
