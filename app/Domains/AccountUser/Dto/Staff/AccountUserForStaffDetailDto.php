<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Dto\Staff;

use App\Domains\AccountUser\Models\AccountUser;

final readonly class AccountUserForStaffDetailDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $status,
        public ?string $emailVerifiedAt,
        public ?string $lastLoginAt,
        public string $createdAt,
        public string $updatedAt,
        public ?string $deletedAt,
    ) {}

    public static function fromModel(AccountUser $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            status: $user->status,
            emailVerifiedAt: $user->email_verified_at?->toISOString(),
            lastLoginAt: $user->last_login_at?->toISOString(),
            createdAt: $user->created_at?->toISOString() ?? '',
            updatedAt: $user->updated_at?->toISOString() ?? '',
            deletedAt: $user->deleted_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'email_verified_at' => $this->emailVerifiedAt,
            'last_login_at' => $this->lastLoginAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
        ];
    }
}
