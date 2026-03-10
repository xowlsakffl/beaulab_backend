<?php

namespace App\Domains\HospitalVideo\Dto\Staff;

use App\Domains\Common\Models\Media\Media;
use App\Domains\HospitalVideo\Models\HospitalVideo;

final readonly class HospitalVideoForStaffDetailDto
{
    public function __construct(public array $video) {}

    public static function fromModel(HospitalVideo $video): self
    {
        return new self([
            'id' => $video->id,
            'hospital_id' => $video->hospital_id,
            'doctor_id' => $video->doctor_id,
            'title' => $video->title,
            'description' => $video->description,
            'distribution_channel' => $video->distribution_channel,
            'external_video_id' => $video->external_video_id,
            'external_video_url' => $video->external_video_url,
            'thumbnail_media_id' => $video->thumbnail_media_id,
            'thumbnail_file' => self::formatMedia($video->thumbnailMedia),
            'duration_seconds' => (int) $video->duration_seconds,
            'status' => $video->status,
            'published_at' => $video->published_at?->toISOString(),
            'view_count' => (int) $video->view_count,
            'like_count' => (int) $video->like_count,
            'publish_start_at' => $video->publish_start_at?->toISOString(),
            'publish_end_at' => $video->publish_end_at?->toISOString(),
            'is_publish_period_unlimited' => (bool) $video->is_publish_period_unlimited,
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
            'created_at' => $media->created_at?->toISOString(),
            'updated_at' => $media->updated_at?->toISOString(),
        ];
    }
}
