<?php

namespace App\Domains\BeautyExpert\Dto\Staff;

use App\Domains\Common\Models\Media\Media;
use App\Domains\BeautyExpert\Models\BeautyExpert;
use Illuminate\Support\Collection;

final readonly class BeautyExpertForStaffDetailDto
{
    public function __construct(
        public array $expert,
    ) {}

    public static function fromModel(BeautyExpert $expert): self
    {
        return new self([
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
            'education_certificate_image' => self::formatMediaList($expert->educationCertificateImages),
            'etc_certificate_image' => self::formatMediaList($expert->etcCertificateImages),
            'created_at' => $expert->created_at?->toISOString(),
            'updated_at' => $expert->updated_at?->toISOString(),
        ]);
    }

    public function toArray(): array
    {
        return $this->expert;
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

    /**
     * @param Collection<int, Media>|iterable<int, Media>|null $mediaList
     */
    private static function formatMediaList(Collection|iterable|null $mediaList): array
    {
        return collect($mediaList)->map(fn (Media $media): array => self::formatMedia($media))->values()->all();
    }
}
