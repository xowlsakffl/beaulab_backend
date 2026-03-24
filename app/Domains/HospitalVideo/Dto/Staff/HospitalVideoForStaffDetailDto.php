<?php

namespace App\Domains\HospitalVideo\Dto\Staff;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Media\Media;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Support\Collection;

final readonly class HospitalVideoForStaffDetailDto
{
    public function __construct(public array $video) {}

    public static function fromModel(HospitalVideo $video): self
    {
        return new self([
            'id' => $video->id,
            'hospital_id' => $video->hospital_id,
            'hospital_name' => $video->hospital?->name,
            'hospital_business_number' => $video->hospital?->businessRegistration?->business_number,
            'doctor_id' => $video->doctor_id,
            'doctor_name' => $video->doctor?->name,
            'title' => $video->title,
            'description' => $video->description,
            'distribution_channel' => $video->distribution_channel,
            'external_video_id' => $video->external_video_id,
            'external_video_url' => $video->external_video_url,
            'thumbnail_media_id' => $video->thumbnailMedia?->id,
            'thumbnail_file' => self::formatMedia($video->thumbnailMedia),
            'video_file_media_id' => $video->videoFileMedia?->id,
            'video_file' => self::formatMedia($video->videoFileMedia),
            'duration_seconds' => (int) $video->duration_seconds,
            'status' => $video->status,
            'allow_status' => $video->allow_status,
            'view_count' => (int) $video->view_count,
            'like_count' => (int) $video->like_count,
            'allowed_at' => $video->allowed_at?->toISOString(),
            'publish_start_at' => $video->publish_start_at?->toISOString(),
            'publish_end_at' => $video->publish_end_at?->toISOString(),
            'is_publish_period_unlimited' => (bool) $video->is_publish_period_unlimited,
            'categories' => self::resolveCategories($video)
                ->map(fn (Category $category): array => [
                    'id' => (int) $category->id,
                    'domain' => (string) ($category->domain ?? ''),
                    'name' => (string) $category->name,
                    'full_path' => (string) ($category->full_path ?? ''),
                    'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
                ])
                ->values()
                ->all(),
            'created_at' => $video->created_at?->toISOString(),
            'updated_at' => $video->updated_at?->toISOString(),
            'deleted_at' => $video->deleted_at?->toISOString(),
        ]);
    }

    public function toArray(): array
    {
        return $this->video;
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
     * @return Collection<int, Category>
     */
    private static function resolveCategories(HospitalVideo $video): Collection
    {
        if (! $video->relationLoaded('categories')) {
            return collect();
        }

        return $video->categories;
    }
}
