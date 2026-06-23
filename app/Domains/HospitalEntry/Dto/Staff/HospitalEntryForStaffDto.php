<?php

declare(strict_types=1);

namespace App\Domains\HospitalEntry\Dto\Staff;

use App\Domains\HospitalEntry\Models\HospitalEntry;

final readonly class HospitalEntryForStaffDto
{
    /**
     * @param  array{code: string, label: string}  $allowStatus
     */
    public function __construct(
        public int $id,
        public ?string $createdAt,
        public string $hospitalName,
        public string $address,
        public string $ceoName,
        public string $applicantName,
        public array $allowStatus,
    ) {}

    public static function fromModel(HospitalEntry $entry): self
    {
        return new self(
            id: (int) $entry->id,
            createdAt: $entry->created_at?->toISOString(),
            hospitalName: (string) $entry->hospital_name,
            address: self::address($entry),
            ceoName: (string) $entry->ceo_name,
            applicantName: (string) $entry->applicant_name,
            allowStatus: [
                'code' => (string) $entry->allow_status,
                'label' => HospitalEntry::allowStatusLabel($entry->allow_status),
            ],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->createdAt,
            'hospital_name' => $this->hospitalName,
            'address' => $this->address,
            'ceo_name' => $this->ceoName,
            'applicant_name' => $this->applicantName,
            'allow_status' => $this->allowStatus,
        ];
    }

    private static function address(HospitalEntry $entry): string
    {
        return trim(implode(' ', array_filter([
            (string) $entry->address,
            $entry->address_detail,
        ])));
    }
}
