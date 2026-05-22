<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Dto\Staff;

/**
 * AccountUserSummaryForStaffDto DTO.
 */
final readonly class AccountUserSummaryForStaffDto
{
    /**
     * @param  list<AccountUserSignupChannelSummaryForStaffDto>  $signupChannels
     */
    public function __construct(
        public int $dailyVisitors,
        public int $monthlyVisitors,
        public int $totalUsers,
        public int $withdrawnUsers,
        public int $blockedUsers,
        public int $warnedUsers,
        public array $signupChannels,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $signupChannels = array_map(
            static fn (array $item): AccountUserSignupChannelSummaryForStaffDto => AccountUserSignupChannelSummaryForStaffDto::fromArray($item),
            array_values(array_filter(
                $data['signup_channels'] ?? [],
                static fn (mixed $item): bool => is_array($item),
            )),
        );

        return new self(
            dailyVisitors: (int) ($data['daily_visitors'] ?? 0),
            monthlyVisitors: (int) ($data['monthly_visitors'] ?? 0),
            totalUsers: (int) ($data['total_users'] ?? 0),
            withdrawnUsers: (int) ($data['withdrawn_users'] ?? 0),
            blockedUsers: (int) ($data['blocked_users'] ?? 0),
            warnedUsers: (int) ($data['warned_users'] ?? 0),
            signupChannels: $signupChannels,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'daily_visitors' => $this->dailyVisitors,
            'monthly_visitors' => $this->monthlyVisitors,
            'total_users' => $this->totalUsers,
            'withdrawn_users' => $this->withdrawnUsers,
            'blocked_users' => $this->blockedUsers,
            'warned_users' => $this->warnedUsers,
            'signup_channels' => array_map(
                static fn (AccountUserSignupChannelSummaryForStaffDto $item): array => $item->toArray(),
                $this->signupChannels,
            ),
        ];
    }
}
