<?php

namespace App\Domains\Beauty\Dto\Staff;

use App\Domains\Beauty\Models\Beauty;
use App\Domains\Common\Models\Media\Media;

final readonly class BeautyForStaffDetailDto
{
    public function __construct(
        public array $beauty,
    ) {}

    public static function fromModel(Beauty $beauty): self
    {
        $logo = Media::query()
            ->for($beauty)
            ->collection('logo')
            ->latest('id')
            ->first();

        $gallery = Media::query()
            ->for($beauty)
            ->collection('gallery')
            ->ordered()
            ->get();

        $businessRegistration = $beauty->businessRegistration;

        return new self(
            beauty: [
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
                'logo' => self::formatMedia($logo),
                'gallery' => $gallery->map(fn (Media $media): array => self::formatMedia($media))->all(),
                'business_registration' => $businessRegistration ? [
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
                ] : null,
            ],
        );
    }

    public function toArray(): array
    {
        return $this->beauty;
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
