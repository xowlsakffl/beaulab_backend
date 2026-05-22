<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Dto\Staff;

use App\Domains\AccountUser\Models\AccountUser;

/**
 * AccountUserForStaffDto DTO.
 */
final readonly class AccountUserForStaffDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $nickname,
        public string $email,
        public ?string $phone,
        public string $signupChannel,
        public string $signupChannelLabel,
        public string $status,
        public string $statusLabel,
        public int $warningCount,
        public ?string $emailVerifiedAt,
        public ?string $lastLoginAt,
        public ?string $lastAccessedAt,
        public ?string $lastAccessIp,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromModel(AccountUser $user): self
    {
        $status = $user->deleted_at !== null ? AccountUser::STATUS_WITHDRAWN : $user->status;
        $statusLabels = AccountUser::statusLabels();
        $signupChannelLabels = AccountUser::signupChannelLabels();
        $signupChannel = $user->signup_channel ?: AccountUser::SIGNUP_CHANNEL_UNKNOWN;

        return new self(
            id: $user->id,
            name: $user->name,
            nickname: $user->nickname,
            email: $user->email,
            phone: $user->phone,
            signupChannel: $signupChannel,
            signupChannelLabel: $signupChannelLabels[$signupChannel] ?? $signupChannelLabels[AccountUser::SIGNUP_CHANNEL_UNKNOWN],
            status: $status,
            statusLabel: $statusLabels[$status] ?? $status,
            warningCount: (int) $user->warning_count,
            emailVerifiedAt: $user->email_verified_at?->toISOString(),
            lastLoginAt: $user->last_login_at?->toISOString(),
            lastAccessedAt: $user->last_accessed_at?->toISOString(),
            lastAccessIp: $user->last_access_ip,
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
            'phone' => $this->phone,
            'signup_channel' => $this->signupChannel,
            'signup_channel_label' => $this->signupChannelLabel,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'warning_count' => $this->warningCount,
            'email_verified_at' => $this->emailVerifiedAt,
            'last_login_at' => $this->lastLoginAt,
            'last_accessed_at' => $this->lastAccessedAt,
            'last_access_ip' => $this->lastAccessIp,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        return $data;
    }
}
