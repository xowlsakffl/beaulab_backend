<?php

declare(strict_types=1);

namespace App\Domains\HospitalEntry\Dto\Staff;

use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalEntry\Models\HospitalEntry;

final readonly class HospitalEntryForStaffDetailDto
{
    /**
     * @param  array<string, mixed>|null  $businessRegistrationFile
     * @param  array<string, mixed>|null  $licenseFile
     * @param  array{code: string, label: string}  $allowStatus
     */
    public function __construct(
        public int $id,
        public string $hospitalName,
        public string $hospitalPhone,
        public string $address,
        public ?string $addressDetail,
        public string $businessNumber,
        public ?array $businessRegistrationFile,
        public string $ceoName,
        public ?string $licenseNumber,
        public ?array $licenseFile,
        public string $applicantName,
        public ?string $applicantPosition,
        public ?string $applicantPhone,
        public ?string $applicantEmail,
        public array $allowStatus,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fromModel(HospitalEntry $entry): self
    {
        return new self(
            id: (int) $entry->id,
            hospitalName: (string) $entry->hospital_name,
            hospitalPhone: (string) $entry->hospital_phone,
            address: (string) $entry->address,
            addressDetail: $entry->address_detail,
            businessNumber: (string) $entry->business_number,
            businessRegistrationFile: self::businessRegistrationFile($entry),
            ceoName: (string) $entry->ceo_name,
            licenseNumber: $entry->license_number,
            licenseFile: self::licenseFile($entry),
            applicantName: (string) $entry->applicant_name,
            applicantPosition: $entry->applicant_position,
            applicantPhone: $entry->applicant_phone,
            applicantEmail: $entry->applicant_email,
            allowStatus: [
                'code' => (string) $entry->allow_status,
                'label' => HospitalEntry::allowStatusLabel($entry->allow_status),
            ],
            createdAt: $entry->created_at?->toISOString(),
            updatedAt: $entry->updated_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'hospital_name' => $this->hospitalName,
            'hospital_phone' => $this->hospitalPhone,
            'address' => $this->address,
            'address_detail' => $this->addressDetail,
            'business_number' => $this->businessNumber,
            'business_registration_file' => $this->businessRegistrationFile,
            'ceo_name' => $this->ceoName,
            'license_number' => $this->licenseNumber,
            'license_file' => $this->licenseFile,
            'applicant_name' => $this->applicantName,
            'applicant_position' => $this->applicantPosition,
            'applicant_phone' => $this->applicantPhone,
            'applicant_email' => $this->applicantEmail,
            'allow_status' => $this->allowStatus,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    private static function businessRegistrationFile(HospitalEntry $entry): ?array
    {
        if (! $entry->relationLoaded('businessRegistrationFile')) {
            return null;
        }

        return self::media($entry->businessRegistrationFile);
    }

    private static function licenseFile(HospitalEntry $entry): ?array
    {
        if (! $entry->relationLoaded('licenseFile')) {
            return null;
        }

        return self::media($entry->licenseFile);
    }

    private static function media(?Media $media): ?array
    {
        if (! $media) {
            return null;
        }

        return [
            'id' => $media->id,
            'collection' => $media->collection,
            'disk' => $media->disk,
            'path' => $media->path,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'width' => $media->width,
            'height' => $media->height,
            'sort_order' => $media->sort_order,
            'is_primary' => (bool) $media->is_primary,
            'metadata' => $media->metadata,
            'created_at' => $media->created_at?->toISOString(),
            'updated_at' => $media->updated_at?->toISOString(),
        ];
    }
}
