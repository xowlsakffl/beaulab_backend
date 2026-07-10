<?php

namespace App\Domains\HospitalVideo\Dto\Hospital;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Hashtag\Models\Hashtag;
use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalVideo\Models\HospitalVideo;

final readonly class HospitalVideoForHospitalDetailDto
{
    public function __construct(
        public int $id,
        public ?array $hospital,
        public ?array $doctor,
        public string $title,
        public ?string $description,
        public ?string $externalVideoUrl,
        public string $hospitalStatus,
        public string $hospitalStatusLabel,
        public string $adminStatus,
        public string $adminStatusLabel,
        public ?array $thumbnailFile,
        public array $categories,
        public array $hashtags,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fromModel(HospitalVideo $video): self
    {
        $hospitalStatus = (string) $video->hospital_status;
        $adminStatus = (string) $video->admin_status;

        return new self(
            id: (int) $video->id,
            hospital: self::hospital($video),
            doctor: self::doctor($video),
            title: (string) $video->title,
            description: $video->description,
            externalVideoUrl: $video->external_video_url,
            hospitalStatus: $hospitalStatus,
            hospitalStatusLabel: HospitalVideo::hospitalStatusLabel($hospitalStatus),
            adminStatus: $adminStatus,
            adminStatusLabel: HospitalVideo::adminStatusLabel($adminStatus),
            thumbnailFile: self::thumbnailFile($video),
            categories: self::categories($video),
            hashtags: self::hashtags($video),
            createdAt: $video->created_at?->toISOString(),
            updatedAt: $video->updated_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'hospital' => $this->hospital,
            'doctor' => $this->doctor,
            'title' => $this->title,
            'description' => $this->description,
            'external_video_url' => $this->externalVideoUrl,
            'hospital_status' => $this->hospitalStatus,
            'hospital_status_label' => $this->hospitalStatusLabel,
            'admin_status' => $this->adminStatus,
            'admin_status_label' => $this->adminStatusLabel,
            'thumbnail_file' => $this->thumbnailFile,
            'categories' => $this->categories,
            'hashtags' => $this->hashtags,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    private static function hospital(HospitalVideo $video): ?array
    {
        if (! $video->relationLoaded('hospital') || ! $video->hospital) {
            return null;
        }

        $businessNumber = null;
        if ($video->hospital->relationLoaded('businessRegistration') && $video->hospital->businessRegistration) {
            $businessNumber = $video->hospital->businessRegistration->business_number;
        }

        return [
            'id' => (int) $video->hospital->id,
            'name' => (string) $video->hospital->name,
            'business_number' => $businessNumber,
        ];
    }

    private static function doctor(HospitalVideo $video): ?array
    {
        if (! $video->relationLoaded('doctor') || ! $video->doctor) {
            return null;
        }

        $attributes = $video->doctor->getAttributes();

        return [
            'id' => (int) $video->doctor->getKey(),
            'name' => (string) ($attributes['name'] ?? ''),
            'position' => $attributes['position'] ?? null,
        ];
    }

    private static function categories(HospitalVideo $video): array
    {
        if (! $video->relationLoaded('categories')) {
            return [];
        }

        return $video->categories
            ->map(fn (Category $category): array => [
                'id' => (int) $category->id,
                'code' => (string) ($category->code ?? ''),
                'domain' => (string) ($category->domain ?? ''),
                'name' => (string) $category->name,
                'full_path' => (string) ($category->full_path ?? ''),
            ])
            ->values()
            ->all();
    }

    private static function hashtags(HospitalVideo $video): array
    {
        if (! $video->relationLoaded('hashtags')) {
            return [];
        }

        return $video->hashtags
            ->map(fn (Hashtag $hashtag): array => [
                'id' => (int) $hashtag->id,
                'name' => (string) $hashtag->name,
                'sort_order' => (int) ($hashtag->pivot?->sort_order ?? 0),
            ])
            ->values()
            ->all();
    }

    private static function thumbnailFile(HospitalVideo $video): ?array
    {
        if (! $video->relationLoaded('thumbnailMedia')) {
            return null;
        }

        return self::media($video->thumbnailMedia);
    }

    private static function media(?Media $media): ?array
    {
        if (! $media) {
            return null;
        }

        return [
            'id' => (int) $media->id,
            'collection' => (string) $media->collection,
            'disk' => (string) $media->disk,
            'path' => (string) $media->path,
            'mime_type' => $media->mime_type,
            'size' => $media->size !== null ? (int) $media->size : null,
            'width' => $media->width !== null ? (int) $media->width : null,
            'height' => $media->height !== null ? (int) $media->height : null,
            'sort_order' => (int) $media->sort_order,
            'is_primary' => (bool) $media->is_primary,
            'metadata' => $media->metadata,
            'created_at' => $media->created_at?->toISOString(),
            'updated_at' => $media->updated_at?->toISOString(),
        ];
    }
}
