<?php

namespace App\Domains\Hospital\Dto\Staff;

use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\OperationHistory\Dto\OperationHistoryDto;
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
     * @param  array<int, array<string, mixed>>  $gallery
     * @param  array<int, array<string, mixed>>  $categories
     * @param  array<int, array<string, mixed>>  $features
     * @param  array<string, mixed>|null  $latestStatusHistory
     * @param  array<string, mixed>|null  $accountHospital
     * @param  array<int, array<string, mixed>>|null  $doctors
     * @param  array<string, mixed>|null  $businessRegistration
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $department,
        public string $departmentLabel,
        public ?string $description,
        public ?string $youtubeLink,
        public ?string $address,
        public ?string $addressDetail,
        public ?string $latitude,
        public ?string $longitude,
        public ?string $tel,
        public array $adReceptionPhones,
        public ?string $consultingHours,
        public ?array $operationHours,
        public ?string $direction,
        public int $viewCount,
        public int $newEventDBCount,
        public string $allowStatus,
        public string $status,
        public ?array $latestStatusHistory,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?array $logo,
        public array $gallery,
        public array $categories,
        public array $features,
        public ?array $accountHospital = null,
        public ?array $doctors = null,
        public ?array $businessRegistration = null,
    ) {}

    public static function fromModel(Hospital $hospital): self
    {
        return new self(
            id: (int) $hospital->id,
            name: (string) $hospital->name,
            department: (string) $hospital->department,
            departmentLabel: $hospital->departmentLabel(),
            description: $hospital->description,
            youtubeLink: $hospital->youtube_link,
            address: $hospital->address,
            addressDetail: $hospital->address_detail,
            latitude: $hospital->latitude,
            longitude: $hospital->longitude,
            tel: $hospital->tel,
            adReceptionPhones: self::adReceptionPhones($hospital),
            consultingHours: $hospital->consulting_hours,
            operationHours: self::operationHours($hospital),
            direction: $hospital->direction,
            viewCount: (int) $hospital->view_count,
            newEventDBCount: (int) ($hospital->new_event_db_count ?? 0),
            allowStatus: (string) $hospital->allow_status,
            status: (string) $hospital->status,
            latestStatusHistory: self::latestStatusHistory($hospital),
            createdAt: $hospital->created_at?->toISOString(),
            updatedAt: $hospital->updated_at?->toISOString(),
            logo: self::logo($hospital),
            gallery: self::gallery($hospital),
            categories: self::categories($hospital),
            features: self::features($hospital),
            accountHospital: self::accountHospital($hospital),
            doctors: self::doctors($hospital),
            businessRegistration: self::businessRegistration($hospital),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'department' => $this->department,
            'department_label' => $this->departmentLabel,
            'description' => $this->description,
            'youtube_link' => $this->youtubeLink,
            'address' => $this->address,
            'address_detail' => $this->addressDetail,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'tel' => $this->tel,
            'ad_reception_phones' => $this->adReceptionPhones,
            'consulting_hours' => $this->consultingHours,
            'operation_hours' => $this->operationHours,
            'direction' => $this->direction,
            'view_count' => $this->viewCount,
            'new_event_db_count' => $this->newEventDBCount,
            'allow_status' => $this->allowStatus,
            'status' => $this->status,
            'latest_status_history' => $this->latestStatusHistory,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'logo' => $this->logo,
            'gallery' => $this->gallery,
            'categories' => $this->categories,
            'features' => $this->features,
        ];

        if ($this->accountHospital !== null) {
            $data['account_hospital'] = $this->accountHospital;
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
     * @return array<string, mixed>|null
     */
    private static function accountHospital(Hospital $hospital): ?array
    {
        if (! $hospital->relationLoaded('accountHospital')) {
            return null;
        }

        $accountHospital = $hospital->accountHospital;

        if (! $accountHospital instanceof AccountHospital) {
            return null;
        }

        return [
            'id' => $accountHospital->id,
            'name' => $accountHospital->name,
            'nickname' => $accountHospital->nickname,
            'email' => $accountHospital->email,
            'phone' => $accountHospital->phone,
            'status' => $accountHospital->status,
            'roles' => $accountHospital->getRoleNames()->values()->all(),
            'last_login_at' => $accountHospital->last_login_at?->toISOString(),
            'created_at' => $accountHospital->created_at?->toISOString(),
            'updated_at' => $accountHospital->updated_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function latestStatusHistory(Hospital $hospital): ?array
    {
        if (! $hospital->relationLoaded('operationHistories')) {
            return null;
        }

        $history = $hospital->operationHistories
            ->first(static function ($history) use ($hospital): bool {
                $change = $history->changes->first();

                return $change?->field_key === 'status'
                    && (string) $change->after_value === (string) $hospital->status;
            });

        return $history ? OperationHistoryDto::fromModel($history)->toArray() : null;
    }

    /**
     * @return array{phone_1: ?string, phone_2: ?string, phone_3: ?string}
     */
    private static function adReceptionPhones(Hospital $hospital): array
    {
        return [
            'phone_1' => $hospital->ad_reception_phone_1,
            'phone_2' => $hospital->ad_reception_phone_2,
            'phone_3' => $hospital->ad_reception_phone_3,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function operationHours(Hospital $hospital): ?array
    {
        $operationHours = $hospital->operation_hours;

        return is_array($operationHours) ? $operationHours : null;
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
                'specialist' => [
                    'code' => (string) ($doctor->specialist_field ?: HospitalDoctor::SPECIALIST_FIELD_NONE),
                    'label' => HospitalDoctor::specialistFieldLabel($doctor->specialist_field),
                ],
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
            'settlement_account' => [
                'bank_name' => $businessRegistration->settlement_bank_name,
                'account_number' => $businessRegistration->settlement_account_number,
                'account_holder' => $businessRegistration->settlement_account_holder,
                'tax_invoice_email' => $businessRegistration->tax_invoice_email,
            ],
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

        $categories = [];

        foreach ($hospital->categories as $category) {
            $rootCategory = self::rootCategory($category);
            $rootCategoryId = (int) $rootCategory->id;

            $categories[$rootCategoryId] ??= [
                'id' => $rootCategoryId,
                'domain' => (string) $rootCategory->domain,
                'parent_id' => $rootCategory->parent_id !== null ? (int) $rootCategory->parent_id : null,
                'depth' => (int) $rootCategory->depth,
                'name' => (string) $rootCategory->name,
                'full_path' => (string) ($rootCategory->full_path ?: $rootCategory->name),
                'is_primary' => false,
            ];

            if ((bool) ($category->pivot?->is_primary ?? false)) {
                $categories[$rootCategoryId]['is_primary'] = true;
            }
        }

        return array_values($categories);
    }

    private static function rootCategory(Category $category): Category
    {
        $current = $category;

        while ($current->relationLoaded('parent') && $current->parent instanceof Category) {
            $current = $current->parent;
        }

        return $current;
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
