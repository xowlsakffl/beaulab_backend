<?php

declare(strict_types=1);

namespace App\Domains\HospitalEntry\Dto\Staff;

final readonly class HospitalEntrySummaryForStaffDto
{
    public function __construct(
        private int $pendingEntries,
        private int $rejectedEntries,
        private int $approvedEntries,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            pendingEntries: (int) ($data['pending_entries'] ?? 0),
            rejectedEntries: (int) ($data['rejected_entries'] ?? 0),
            approvedEntries: (int) ($data['approved_entries'] ?? 0),
        );
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'pending_entries' => $this->pendingEntries,
            'rejected_entries' => $this->rejectedEntries,
            'approved_entries' => $this->approvedEntries,
        ];
    }
}
