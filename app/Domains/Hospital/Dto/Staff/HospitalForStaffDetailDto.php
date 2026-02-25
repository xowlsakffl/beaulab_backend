<?php

namespace App\Domains\Hospital\Dto\Staff;

use App\Domains\Common\Models\Media\Media;
use Illuminate\Support\Collection;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Doctor\Models\Doctor;
use App\Domains\Partner\Models\AccountPartner;

final readonly class HospitalForStaffDetailDto
{
    public function __construct(
        public array $hospital,
    ) {}

    /**
     * @param array<int, string> $include
     */
    public static function fromModel(Hospital $hospital, array $include = []): self
    {

        $payload = [
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
            'logo' => self::formatMedia(self::resolveLogo($hospital)),
            'gallery' => self::resolveGallery($hospital)->map(fn (Media $media): array => self::formatMedia($media))->all(),
        ];

        if (in_array('account_partners', $include, true)) {
            $payload['account_partners'] = $hospital->partners->map(fn (AccountPartner $partner): array => [
                'id' => $partner->id,
                'name' => $partner->name,
                'nickname' => $partner->nickname,
                'email' => $partner->email,
                'partner_type' => $partner->partner_type,
                'status' => $partner->status,
                'roles' => $partner->getRoleNames()->values()->all(),
                'last_login_at' => $partner->last_login_at?->toISOString(),
                'created_at' => $partner->created_at?->toISOString(),
                'updated_at' => $partner->updated_at?->toISOString(),
            ])->all();
        }


        if (in_array('doctors', $include, true)) {
            $payload['doctors'] = $hospital->doctors->map(fn (Doctor $doctor): array => [
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
            ])->all();
        }

        if (in_array('business_registration', $include, true)) {
            $businessRegistration = $hospital->businessRegistration;
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

        return new self(hospital: $payload);
    }

    public function toArray(): array
    {
        return $this->hospital;
    }

    private static function resolveLogo(Hospital $hospital): ?Media
    {
        if (! $hospital->relationLoaded('logoMedia')) {
            return null;
        }

        return $hospital->logoMedia;
    }

    /**
     * @return Collection<int, Media>
     */
    private static function resolveGallery(Hospital $hospital): Collection
    {
        if (! $hospital->relationLoaded('galleryMedia')) {
            return collect();
        }

        return $hospital->galleryMedia;
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
