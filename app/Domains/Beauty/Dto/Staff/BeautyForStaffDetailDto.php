<?php

namespace App\Domains\Beauty\Dto\Staff;

use App\Domains\AccountBeauty\Models\AccountBeauty;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\BeautyExpert\Models\BeautyExpert;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;

/**
 * BeautyForStaffDetailDto 역할 정의.
 * 뷰티 도메인의 DTO로, 모델 값을 API 응답이나 계층 간 전달에 맞는 단순한 배열/값 구조로 정규화한다.
 */
final readonly class BeautyForStaffDetailDto
{
    /**
     * @param  array<int, array<string, mixed>>  $gallery
     * @param  array<int, array<string, mixed>>  $categories
     * @param  array<string, mixed>|null  $accountBeauty
     * @param  array<int, array<string, mixed>>|null  $experts
     * @param  array<string, mixed>|null  $businessRegistration
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
        public ?array $accountBeauty = null,
        public ?array $experts = null,
        public ?array $businessRegistration = null,
    ) {}

    public static function fromModel(Beauty $beauty): self
    {
        return new self(
            id: (int) $beauty->id,
            name: (string) $beauty->name,
            description: $beauty->description,
            address: $beauty->address,
            addressDetail: $beauty->address_detail,
            latitude: $beauty->latitude,
            longitude: $beauty->longitude,
            tel: $beauty->tel,
            email: $beauty->email,
            consultingHours: $beauty->consulting_hours,
            direction: $beauty->direction,
            viewCount: (int) $beauty->view_count,
            allowStatus: (string) $beauty->allow_status,
            status: (string) $beauty->status,
            createdAt: $beauty->created_at?->toISOString(),
            updatedAt: $beauty->updated_at?->toISOString(),
            logo: self::logo($beauty),
            gallery: self::gallery($beauty),
            categories: self::categories($beauty),
            accountBeauty: self::accountBeauty($beauty),
            experts: self::experts($beauty),
            businessRegistration: self::businessRegistration($beauty),
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
        ];

        if ($this->accountBeauty !== null) {
            $data['account_beauty'] = $this->accountBeauty;
        }

        if ($this->experts !== null) {
            $data['experts'] = $this->experts;
        }

        if ($this->businessRegistration !== null) {
            $data['business_registration'] = $this->businessRegistration;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function accountBeauty(Beauty $beauty): ?array
    {
        if (! $beauty->relationLoaded('accountBeauty')) {
            return null;
        }

        $accountBeauty = $beauty->accountBeauty;

        if (! $accountBeauty instanceof AccountBeauty) {
            return null;
        }

        return [
            'id' => $accountBeauty->id,
            'name' => $accountBeauty->name,
            'nickname' => $accountBeauty->nickname,
            'email' => $accountBeauty->email,
            'status' => $accountBeauty->status,
            'roles' => $accountBeauty->getRoleNames()->values()->all(),
            'last_login_at' => $accountBeauty->last_login_at?->toISOString(),
            'created_at' => $accountBeauty->created_at?->toISOString(),
            'updated_at' => $accountBeauty->updated_at?->toISOString(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private static function experts(Beauty $beauty): ?array
    {
        if (! $beauty->relationLoaded('experts')) {
            return null;
        }

        return $beauty->experts
            ->map(fn (BeautyExpert $expert): array => [
                'id' => $expert->id,
                'beauty_id' => $expert->beauty_id,
                'sort_order' => (int) $expert->sort_order,
                'name' => $expert->name,
                'gender' => $expert->gender,
                'position' => $expert->position,
                'career_started_at' => $expert->career_started_at?->toDateString(),
                'educations' => $expert->educations ?? [],
                'careers' => $expert->careers ?? [],
                'etc_contents' => $expert->etc_contents ?? [],
                'status' => $expert->status,
                'allow_status' => $expert->allow_status,
                'profile_image' => self::media($expert->profileImage),
                'created_at' => $expert->created_at?->toISOString(),
                'updated_at' => $expert->updated_at?->toISOString(),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function businessRegistration(Beauty $beauty): ?array
    {
        if (! $beauty->relationLoaded('businessRegistration')) {
            return null;
        }

        $businessRegistration = $beauty->businessRegistration;

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

    private static function logo(Beauty $beauty): ?array
    {
        if (! $beauty->relationLoaded('logoMedia')) {
            return null;
        }

        return self::media($beauty->logoMedia);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function gallery(Beauty $beauty): array
    {
        if (! $beauty->relationLoaded('galleryMedia')) {
            return [];
        }

        return $beauty->galleryMedia
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
            'path' => $media->publicPath(),
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'width' => $media->width,
            'height' => $media->height,
            'sort_order' => $media->sort_order,
            'is_primary' => (bool) $media->is_primary,
            'metadata' => $media->publicMetadata(),
            'created_at' => $media->created_at?->toISOString(),
            'updated_at' => $media->updated_at?->toISOString(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function categories(Beauty $beauty): array
    {
        if (! $beauty->relationLoaded('categories')) {
            return [];
        }

        return $beauty->categories
            ->map(fn (Category $category): array => [
                'id' => (int) $category->id,
                'name' => (string) $category->name,
                'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
            ])
            ->values()
            ->all();
    }
}
