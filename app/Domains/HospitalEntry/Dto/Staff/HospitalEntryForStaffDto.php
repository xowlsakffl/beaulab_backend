<?php

declare(strict_types=1);

namespace App\Domains\HospitalEntry\Dto\Staff;

use App\Domains\HospitalEntry\Models\HospitalEntry;

final readonly class HospitalEntryForStaffDto
{
    public function __construct(private HospitalEntry $entry) {}

    public static function fromModel(HospitalEntry $entry): self
    {
        return new self($entry);
    }

    public function toArray(): array
    {
        return [
            'id' => (int) $this->entry->id,
            'applied_at' => $this->entry->created_at?->toISOString(),
            'created_at' => $this->entry->created_at?->toISOString(),
            'hospital_name' => (string) $this->entry->hospital_name,
            'address' => $this->address(),
            'ceo_name' => (string) $this->entry->ceo_name,
            'applicant_name' => (string) $this->entry->applicant_name,
            'allow_status' => [
                'code' => (string) $this->entry->allow_status,
                'label' => HospitalEntry::allowStatusLabel($this->entry->allow_status),
            ],
        ];
    }

    private function address(): string
    {
        return trim(implode(' ', array_filter([
            (string) $this->entry->address,
            $this->entry->address_detail,
        ])));
    }
}
