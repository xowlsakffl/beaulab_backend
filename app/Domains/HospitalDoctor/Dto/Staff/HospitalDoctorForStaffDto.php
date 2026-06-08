<?php

namespace App\Domains\HospitalDoctor\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;

/**
 * HospitalDoctorForStaffDto 역할 정의.
 * 병원 의사 도메인의 DTO로, 모델 값을 API 응답이나 계층 간 전달에 맞는 단순한 배열/값 구조로 정규화한다.
 */
final readonly class HospitalDoctorForStaffDto
{
    public function __construct(
        public int $id,
        public int $hospitalId,
        public ?string $hospitalName,
        public string $name,
        public ?string $gender,
        public ?string $position,
        public array $specialist,
        public ?string $careerStartedAt,
        public ?string $licenseNumber,
        public string $allowStatus,
        public int $reviewCount,
        public int $consultationCount,
        public string $createdAt,
        public ?array $profileImage,
        public ?array $categories = null,
    ) {}

    public static function fromModel(HospitalDoctor $doctor): self
    {
        return new self(
            id: (int) $doctor->id,
            hospitalId: (int) $doctor->hospital_id,
            hospitalName: $doctor->relationLoaded('hospital') ? $doctor->hospital?->name : null,
            name: (string) $doctor->name,
            gender: $doctor->gender,
            position: $doctor->position,
            specialist: self::specialist($doctor),
            careerStartedAt: $doctor->career_started_at?->toDateString(),
            licenseNumber: $doctor->license_number,
            allowStatus: (string) $doctor->allow_status,
            reviewCount: (int) ($doctor->review_count ?? 0),
            consultationCount: (int) ($doctor->consultation_count ?? 0),
            createdAt: $doctor->created_at?->toISOString() ?? '',
            profileImage: self::profileImage($doctor),
            categories: self::categories($doctor),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'hospital_id' => $this->hospitalId,
            'hospital_name' => $this->hospitalName,
            'name' => $this->name,
            'gender' => $this->gender,
            'position' => $this->position,
            'specialist' => $this->specialist,
            'career_started_at' => $this->careerStartedAt,
            'license_number' => $this->licenseNumber,
            'allow_status' => $this->allowStatus,
            'review_count' => $this->reviewCount,
            'consultation_count' => $this->consultationCount,
            'created_at' => $this->createdAt,
            'profile_image' => $this->profileImage,
        ];

        if ($this->categories !== null) {
            $data['categories'] = $this->categories;
        }

        return $data;
    }

    private static function profileImage(HospitalDoctor $doctor): ?array
    {
        if (! $doctor->relationLoaded('profileImage')) {
            return null;
        }

        return self::media($doctor->profileImage);
    }

    private static function specialist(HospitalDoctor $doctor): array
    {
        $code = (string) ($doctor->specialist_field ?: HospitalDoctor::SPECIALIST_FIELD_NONE);

        return [
            'code' => $code,
            'label' => HospitalDoctor::specialistFieldLabel($code),
        ];
    }

    private static function categories(HospitalDoctor $doctor): ?array
    {
        if (! $doctor->relationLoaded('categories')) {
            return null;
        }

        return $doctor->categories
            ->map(static function (Category $category): string {
                $fullPath = trim((string) ($category->full_path ?: $category->name));
                $rootName = trim(explode('>', $fullPath)[0] ?? '');

                return $rootName !== '' ? $rootName : (string) $category->name;
            })
            ->filter(static fn (string $name): bool => $name !== '')
            ->unique()
            ->values()
            ->map(static fn (string $name): array => ['name' => $name])
            ->values()
            ->all();
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
