<?php

namespace App\Domains\HospitalDoctor\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;

/**
 * HospitalDoctorForStaffDetailDto DTO.
 */
final readonly class HospitalDoctorForStaffDetailDto
{
    /**
     * @param array<int, mixed> $educations
     * @param array<int, mixed> $careers
     * @param array<int, mixed> $etcContents
     * @param array<int, array<string, mixed>> $educationCertificateImage
     * @param array<int, array<string, mixed>> $etcCertificateImage
     * @param array<int, array<string, mixed>> $categories
     */
    public function __construct(
        public int $id,
        public int $hospitalId,
        public ?string $hospitalName,
        public ?string $hospitalBusinessNumber,
        public int $sortOrder,
        public string $name,
        public ?string $gender,
        public ?string $position,
        public ?string $careerStartedAt,
        public ?string $licenseNumber,
        public bool $isSpecialist,
        public int $viewCount,
        public array $educations,
        public array $careers,
        public array $etcContents,
        public string $status,
        public string $allowStatus,
        public ?array $profileImage,
        public ?array $licenseImage,
        public ?array $specialistCertificateImage,
        public array $educationCertificateImage,
        public array $etcCertificateImage,
        public array $categories,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fromModel(HospitalDoctor $doctor): self
    {
        return new self(
            id: (int) $doctor->id,
            hospitalId: (int) $doctor->hospital_id,
            hospitalName: $doctor->hospital?->name,
            hospitalBusinessNumber: $doctor->hospital?->businessRegistration?->business_number,
            sortOrder: (int) $doctor->sort_order,
            name: (string) $doctor->name,
            gender: $doctor->gender,
            position: $doctor->position,
            careerStartedAt: $doctor->career_started_at?->toDateString(),
            licenseNumber: $doctor->license_number,
            isSpecialist: (bool) $doctor->is_specialist,
            viewCount: (int) $doctor->view_count,
            educations: $doctor->educations ?? [],
            careers: $doctor->careers ?? [],
            etcContents: $doctor->etc_contents ?? [],
            status: (string) $doctor->status,
            allowStatus: (string) $doctor->allow_status,
            profileImage: self::profileImage($doctor),
            licenseImage: self::licenseImage($doctor),
            specialistCertificateImage: self::specialistCertificateImage($doctor),
            educationCertificateImage: self::educationCertificateImage($doctor),
            etcCertificateImage: self::etcCertificateImage($doctor),
            categories: self::categories($doctor),
            createdAt: $doctor->created_at?->toISOString(),
            updatedAt: $doctor->updated_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'hospital_id' => $this->hospitalId,
            'hospital_name' => $this->hospitalName,
            'hospital_business_number' => $this->hospitalBusinessNumber,
            'sort_order' => $this->sortOrder,
            'name' => $this->name,
            'gender' => $this->gender,
            'position' => $this->position,
            'career_started_at' => $this->careerStartedAt,
            'license_number' => $this->licenseNumber,
            'is_specialist' => $this->isSpecialist,
            'view_count' => $this->viewCount,
            'educations' => $this->educations,
            'careers' => $this->careers,
            'etc_contents' => $this->etcContents,
            'status' => $this->status,
            'allow_status' => $this->allowStatus,
            'profile_image' => $this->profileImage,
            'license_image' => $this->licenseImage,
            'specialist_certificate_image' => $this->specialistCertificateImage,
            'education_certificate_image' => $this->educationCertificateImage,
            'etc_certificate_image' => $this->etcCertificateImage,
            'categories' => $this->categories,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        return $data;
    }

    private static function profileImage(HospitalDoctor $doctor): ?array
    {
        if (! $doctor->relationLoaded('profileImage')) {
            return null;
        }

        return self::media($doctor->profileImage);
    }

    private static function licenseImage(HospitalDoctor $doctor): ?array
    {
        if (! $doctor->relationLoaded('licenseImage')) {
            return null;
        }

        return self::media($doctor->licenseImage);
    }

    private static function specialistCertificateImage(HospitalDoctor $doctor): ?array
    {
        if (! $doctor->relationLoaded('specialistCertificateImages')) {
            return null;
        }

        return self::media($doctor->specialistCertificateImages->first());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function educationCertificateImage(HospitalDoctor $doctor): array
    {
        if (! $doctor->relationLoaded('educationCertificateImages')) {
            return [];
        }

        return self::mediaList($doctor->educationCertificateImages);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function etcCertificateImage(HospitalDoctor $doctor): array
    {
        if (! $doctor->relationLoaded('etcCertificateImages')) {
            return [];
        }

        return self::mediaList($doctor->etcCertificateImages);
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

    /** @return array<int, array<string, mixed>> */
    private static function mediaList(iterable $mediaList): array
    {
        return collect($mediaList)
            ->map(fn (Media $media): array => self::media($media) ?? [])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function categories(HospitalDoctor $doctor): array
    {
        if (! $doctor->relationLoaded('categories')) {
            return [];
        }

        return $doctor->categories
            ->map(fn (Category $category): array => [
                'id' => (int) $category->id,
                'domain' => (string) $category->domain,
                'name' => (string) $category->name,
                'full_path' => (string) ($category->full_path ?: $category->name),
                'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
            ])
            ->values()
            ->all();
    }
}
