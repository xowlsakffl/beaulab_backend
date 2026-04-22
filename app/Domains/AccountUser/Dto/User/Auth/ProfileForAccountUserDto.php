<?php

namespace App\Domains\AccountUser\Dto\User\Auth;

use App\Domains\AccountUser\Models\AccountUser;

/**
 * ProfileForAccountUserDto DTO.
 */
final readonly class ProfileForAccountUserDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $nickname,
        public string $email,
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
            status: (string) $user->status,
            emailVerifiedAt: $user->email_verified_at?->toISOString(),
            lastLoginAt: $user->last_login_at?->toISOString(),
            createdAt: $user->created_at?->toISOString() ?? '',
            updatedAt: $user->updated_at?->toISOString() ?? '',
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'status' => $this->status,
            'email_verified_at' => $this->emailVerifiedAt,
            'last_login_at' => $this->lastLoginAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        return $data;
    }
}
