<?php

namespace App\Domains\HospitalDoctor\Dto\Staff;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Media\Media;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Illuminate\Support\Collection;

/**
 * HospitalDoctorForStaffDetailDto 역할 정의.
 * 병원 의사 도메인의 DTO로, 모델 값을 API 응답이나 계층 간 전달에 맞는 단순한 배열/값 구조로 정규화한다.
 */
final readonly class HospitalDoctorForStaffDetailDto
{
    public function __construct(
        public array $doctor,
    ) {}

    public static function fromModel(HospitalDoctor $doctor): self
    {
        return new self([
            'id' => $doctor->id,
            'hospital_id' => $doctor->hospital_id,
            'hospital_name' => $doctor->hospital?->name,
            'hospital_business_number' => $doctor->hospital?->businessRegistration?->business_number,
            'sort_order' => (int) $doctor->sort_order,
            'name' => $doctor->name,
            'gender' => $doctor->gender,
            'position' => $doctor->position,
            'career_started_at' => $doctor->career_started_at?->toDateString(),
            'license_number' => $doctor->license_number,
            'is_specialist' => (bool) $doctor->is_specialist,
            'view_count' => (int) $doctor->view_count,
            'educations' => $doctor->educations ?? [],
            'careers' => $doctor->careers ?? [],
            'etc_contents' => $doctor->etc_contents ?? [],
            'status' => $doctor->status,
            'allow_status' => $doctor->allow_status,
            'profile_image' => self::formatMedia($doctor->profileImage),
            'license_image' => self::formatMedia($doctor->licenseImage),
            'specialist_certificate_image' => self::formatMedia($doctor->specialistCertificateImages->first()),
            'education_certificate_image' => self::formatMediaList($doctor->educationCertificateImages),
            'etc_certificate_image' => self::formatMediaList($doctor->etcCertificateImages),
            'categories' => self::resolveCategories($doctor)
                ->map(fn (Category $category): array => [
                    'id' => (int) $category->id,
                    'domain' => (string) $category->domain,
                    'name' => (string) $category->name,
                    'full_path' => (string) ($category->full_path ?: $category->name),
                    'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
                ])
                ->values()
                ->all(),
            'created_at' => $doctor->created_at?->toISOString(),
            'updated_at' => $doctor->updated_at?->toISOString(),
        ]);
    }

    public function toArray(): array
    {
        return $this->doctor;
    }

    private static function formatMedia(?Media $media): ?array
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
            'created_at' => $media->created_at?->toISOString(),
            'updated_at' => $media->updated_at?->toISOString(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function formatMediaList(Collection $mediaList): array
    {
        return $mediaList->map(fn (Media $media): array => self::formatMedia($media) ?? [])->all();
    }

    /**
     * @return Collection<int, Category>
     */
    private static function resolveCategories(HospitalDoctor $doctor): Collection
    {
        if (! $doctor->relationLoaded('categories')) {
            return collect();
        }

        return $doctor->categories;
    }
}
