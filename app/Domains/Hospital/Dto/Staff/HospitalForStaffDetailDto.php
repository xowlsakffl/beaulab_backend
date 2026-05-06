<?php

namespace App\Domains\Hospital\Dto\Staff;

use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalFeature\Models\HospitalFeature;

/**
 * HospitalForStaffDetailDto 역할 정의.
 * 병원 도메인의 DTO로, 모델 값을 API 응답이나 계층 간 전달에 맞는 단순한 배열/값 구조로 정규화한다.
 */
final readonly class HospitalForStaffDetailDto
{
    /**
     * @param array<int, array<string, mixed>> $gallery
     * @param array<int, array<string, mixed>> $categories
     * @param array<int, array<string, mixed>> $features
     * @param array<int, array<string, mixed>>|null $accountHospitals
     * @param array<int, array<string, mixed>>|null $doctors
     * @param array<string, mixed>|null $businessRegistration
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public ?string $address,
        public ?string $addressDetail,
        public ?string $latitude,
        public ?string $longitude,
        public ?string $tel,
        public ?string $email,
        public ?string $consultingHours,
        public ?string $direction,
        public int $viewCount,
        public string $allowStatus,
        public string $status,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?array $logo,
        public array $gallery,
        public array $categories,
        public array $features,
        public ?array $accountHospitals = null,
        public ?array $doctors = null,
        public ?array $businessRegistration = null,
    ) {}

    public static function fromModel(Hospital $hospital): self
    {
        return new self(
            id: (int) $hospital->id,
            name: (string) $hospital->name,
            description: $hospital->description,
            address: $hospital->address,
            addressDetail: $hospital->address_detail,
            latitude: $hospital->latitude,
            longitude: $hospital->longitude,
            tel: $hospital->tel,
            email: $hospital->email,
            consultingHours: $hospital->consulting_hours,
            direction: $hospital->direction,
            viewCount: (int) $hospital->view_count,
            allowStatus: (string) $hospital->allow_status,
            status: (string) $hospital->status,
            createdAt: $hospital->created_at?->toISOString(),
            updatedAt: $hospital->updated_at?->toISOString(),
            logo: self::logo($hospital),
            gallery: self::gallery($hospital),
            categories: self::categories($hospital),
            features: self::features($hospital),
            accountHospitals: self::accountHospitals($hospital),
            doctors: self::doctors($hospital),
            businessRegistration: self::businessRegistration($hospital),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'address' => $this->address,
            'address_detail' => $this->addressDetail,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'tel' => $this->tel,
            'email' => $this->email,
            'consulting_hours' => $this->consultingHours,
            'direction' => $this->direction,
            'view_count' => $this->viewCount,
            'allow_status' => $this->allowStatus,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'logo' => $this->logo,
            'gallery' => $this->gallery,
            'categories' => $this->categories,
            'features' => $this->features,
        ];

        if ($this->accountHospitals !== null) {
            $data['account_hospitals'] = $this->accountHospitals;
        }

        if ($this->doctors !== null) {
            $data['doctors'] = $this->doctors;
        }

        if ($this->businessRegistration !== null) {
            $data['business_registration'] = $this->businessRegistration;
        }

        return $data;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private static function accountHospitals(Hospital $hospital): ?array
    {
        if (! $hospital->relationLoaded('accountHospitals')) {
            return null;
        }

        return $hospital->accountHospitals
            ->map(fn (AccountHospital $accountHospital): array => [
                'id' => $accountHospital->id,
                'name' => $accountHospital->name,
                'nickname' => $accountHospital->nickname,
                'email' => $accountHospital->email,
                'status' => $accountHospital->status,
                'roles' => $accountHospital->getRoleNames()->values()->all(),
                'last_login_at' => $accountHospital->last_login_at?->toISOString(),
                'created_at' => $accountHospital->created_at?->toISOString(),
                'updated_at' => $accountHospital->updated_at?->toISOString(),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private static function doctors(Hospital $hospital): ?array
    {
        if (! $hospital->relationLoaded('doctors')) {
            return null;
        }

        return $hospital->doctors
            ->map(fn (HospitalDoctor $doctor): array => [
                'id' => $doctor->id,
                'hospital_id' => $doctor->hospital_id,
                'name' => $doctor->name,
                'position' => $doctor->position,
                'is_specialist' => (bool) $doctor->is_specialist,
                'sort_order' => (int) $doctor->sort_order,
                'allow_status' => $doctor->allow_status,
                'status' => $doctor->status,
                'created_at' => $doctor->created_at?->toISOString(),
                'updated_at' => $doctor->updated_at?->toISOString(),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function businessRegistration(Hospital $hospital): ?array
    {
        if (! $hospital->relationLoaded('businessRegistration')) {
            return null;
        }

        $businessRegistration = $hospital->businessRegistration;

        if (! $businessRegistration) {
            return null;
        }

        return [
            'id' => $businessRegistration->id,
            'business_number' => $businessRegistration->business_number,
            'company_name' => $businessRegistration->company_name,
            'ceo_name' => $businessRegistration->ceo_name,
            'business_type' => $businessRegistration->business_type,
            'business_item' => $businessRegistration->business_item,
            'business_address' => $businessRegistration->business_address,
            'business_address_detail' => $businessRegistration->business_address_detail,
            'issued_at' => $businessRegistration->issued_at?->toDateString(),
            'status' => $businessRegistration->status,
            'certificate_media' => self::media($businessRegistration->certificateMedia),
        ];
    }

    private static function logo(Hospital $hospital): ?array
    {
        if (! $hospital->relationLoaded('logoMedia')) {
            return null;
        }

        return self::media($hospital->logoMedia);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function gallery(Hospital $hospital): array
    {
        if (! $hospital->relationLoaded('galleryMedia')) {
            return [];
        }

        return $hospital->galleryMedia
            ->map(fn (Media $media): array => self::media($media) ?? [])
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function categories(Hospital $hospital): array
    {
        if (! $hospital->relationLoaded('categories')) {
            return [];
        }

        return $hospital->categories
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function features(Hospital $hospital): array
    {
        if (! $hospital->relationLoaded('features')) {
            return [];
        }

        return $hospital->features
            ->map(fn (HospitalFeature $feature): array => [
                'id' => (int) $feature->id,
                'code' => (string) $feature->code,
                'name' => (string) $feature->name,
                'sort_order' => (int) $feature->sort_order,
                'status' => (string) $feature->status,
            ])
            ->values()
            ->all();
    }
}
