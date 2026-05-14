<?php

namespace App\Domains\AccountUser\Dto\User\Auth;

use App\Domains\AccountUser\Models\AccountUser;

/**
 * AccountUserForAccountUserDto DTO.
 */
final readonly class AccountUserForAccountUserDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $nickname,
        public string $email,
        public ?string $phone,
        public string $status,
        public ?string $emailVerifiedAt,
        public ?string $lastLoginAt,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromModel(AccountUser $user): self
    {
        return new self(
            id: (int) $user->id,
            name: (string) $user->name,
            nickname: (string) $user->nickname,
            email: (string) $user->email,
            phone: $user->phone,
            status: (string) $user->status,
            emailVerifiedAt: $user->email_verified_at?->toISOString(),
            lastLoginAt: $user->last_login_at?->toISOString(),
            createdAt: $user->created_at?->toISOString() ?? '',
            updatedAt: $user->updated_at?->toISOString() ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'email_verified_at' => $this->emailVerifiedAt,
            'last_login_at' => $this->lastLoginAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
