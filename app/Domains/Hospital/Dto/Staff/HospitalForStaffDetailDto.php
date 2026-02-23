<?php

namespace App\Domains\Hospital\Dto\Staff;

use App\Domains\Common\Models\Media\Media;
use App\Domains\Hospital\Models\Hospital;

final readonly class HospitalForStaffDetailDto
{
    public function __construct(
        public array $hospital,
    ) {}

    public static function fromModel(Hospital $hospital): self
    {
        $logo = Media::query()
            ->for($hospital)
            ->collection('logo')
            ->latest('id')
            ->first();

        $gallery = Media::query()
            ->for($hospital)
            ->collection('gallery')
            ->ordered()
            ->get();

        $businessRegistration = $hospital->businessRegistration;

        return new self(
            hospital: [
                'id' => $hospital->id,
                'name' => $hospital->name,
                'description' => $hospital->description,
                'address' => $hospital->address,
                'address_detail' => $hospital->address_detail,
                'latitude' => $hospital->latitude,
                'longitude' => $hospital->longitude,
                'tel' => $hospital->tel,
                'email' => $hospital->email,
                'consulting_hours' => $hospital->consulting_hours,
                'direction' => $hospital->direction,
                'view_count' => (int) $hospital->view_count,
                'allow_status' => $hospital->allow_status,
                'status' => $hospital->status,
                'created_at' => $hospital->created_at?->toISOString(),
                'updated_at' => $hospital->updated_at?->toISOString(),
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
        return $this->hospital;
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
