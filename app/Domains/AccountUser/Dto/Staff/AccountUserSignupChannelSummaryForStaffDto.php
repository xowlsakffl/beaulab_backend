<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Dto\Staff;

/**
 * AccountUserSignupChannelSummaryForStaffDto DTO.
 */
final readonly class AccountUserSignupChannelSummaryForStaffDto
{
    public function __construct(
        public string $channel,
        public string $label,
        public int $count,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            channel: (string) ($data['channel'] ?? ''),
            label: (string) ($data['label'] ?? ''),
            count: (int) ($data['count'] ?? 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'channel' => $this->channel,
            'label' => $this->label,
            'count' => $this->count,
        ];
    }
}
