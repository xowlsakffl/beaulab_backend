<?php

namespace App\Domains\Beauty\Dto\Staff;

use App\Domains\AccountBeauty\Models\AccountBeauty;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\BeautyExpert\Models\BeautyExpert;
use App\Domains\Common\Models\Media\Media;
use Illuminate\Support\Collection;

final readonly class BeautyForStaffDetailDto
{
    public function __construct(
        public array $beauty,
    ) {}

    /**
     * @param array<int, string> $include
     */
    public static function fromModel(Beauty $beauty, array $include = []): self
    {

        $payload = [
            'id' => $beauty->id,
            'name' => $beauty->name,
            'description' => $beauty->description,
            'address' => $beauty->address,
            'address_detail' => $beauty->address_detail,
            'latitude' => $beauty->latitude,
            'longitude' => $beauty->longitude,
            'tel' => $beauty->tel,
            'email' => $beauty->email,
            'consulting_hours' => $beauty->consulting_hours,
            'direction' => $beauty->direction,
            'view_count' => (int) $beauty->view_count,
            'allow_status' => $beauty->allow_status,
            'status' => $beauty->status,
            'created_at' => $beauty->created_at?->toISOString(),
            'updated_at' => $beauty->updated_at?->toISOString(),
            'logo' => self::formatMedia(self::resolveLogo($beauty)),
            'gallery' => self::resolveGallery($beauty)->map(fn (Media $media): array => self::formatMedia($media))->all(),
        ];

        if (in_array('account_beauties', $include, true)) {
            $payload['account_beauties'] = $beauty->accountBeauties->map(fn (AccountBeauty $accountBeauty): array => [
                'id' => $accountBeauty->id,
                'name' => $accountBeauty->name,
                'nickname' => $accountBeauty->nickname,
                'email' => $accountBeauty->email,
                'status' => $accountBeauty->status,
                'roles' => $accountBeauty->getRoleNames()->values()->all(),
                'last_login_at' => $accountBeauty->last_login_at?->toISOString(),
                'created_at' => $accountBeauty->created_at?->toISOString(),
                'updated_at' => $accountBeauty->updated_at?->toISOString(),
            ])->all();
        }

        if (in_array('experts', $include, true)) {
            $payload['experts'] = $beauty->experts->map(fn (BeautyExpert $expert): array => [
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
                'profile_image' => self::formatMedia($expert->profileImage),
                'created_at' => $expert->created_at?->toISOString(),
                'updated_at' => $expert->updated_at?->toISOString(),
            ])->all();
        }

        if (in_array('business_registration', $include, true)) {
            $businessRegistration = $beauty->businessRegistration;
            $payload['business_registration'] = $businessRegistration ? [
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
                'certificate_media' => self::formatMedia($businessRegistration->certificateMedia),
            ] : null;
        }

        return new self(beauty: $payload);
    }

    public function toArray(): array
    {
        return $this->beauty;
    }

    private static function resolveLogo(Beauty $beauty): ?Media
    {
        if (! $beauty->relationLoaded('logoMedia')) {
            return null;
        }

        return $beauty->logoMedia;
    }

    /**
     * @return Collection<int, Media>
     */
    private static function resolveGallery(Beauty $beauty): Collection
    {
        if (! $beauty->relationLoaded('galleryMedia')) {
            return collect();
        }

        return $beauty->galleryMedia;
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
            'is_primary' => (bool) $media->is_primary,
            'metadata' => $media->metadata,
            'created_at' => $media->created_at?->toISOString(),
            'updated_at' => $media->updated_at?->toISOString(),
        ];
    }
}
