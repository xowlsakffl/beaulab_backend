<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Dto\Staff;

/**
 * AccountUserSummaryForStaffDto DTO.
 */
final readonly class AccountUserSummaryForStaffDto
{
    public function __construct(
        public int $withdrawnUsers,
        public int $blockedUsers,
        public int $warnedUsers,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            withdrawnUsers: (int) ($data['withdrawn_users'] ?? 0),
            blockedUsers: (int) ($data['blocked_users'] ?? 0),
            warnedUsers: (int) ($data['warned_users'] ?? 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'withdrawn_users' => $this->withdrawnUsers,
            'blocked_users' => $this->blockedUsers,
            'warned_users' => $this->warnedUsers,
        ];
    }
}
