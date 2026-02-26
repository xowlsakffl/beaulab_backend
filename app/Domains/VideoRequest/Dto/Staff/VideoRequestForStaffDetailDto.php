<?php

namespace App\Domains\VideoRequest\Dto\Staff;

use App\Domains\Common\Models\Media\Media;
use App\Domains\VideoRequest\Models\VideoRequest;

final readonly class VideoRequestForStaffDetailDto
{
    public function __construct(public array $videoRequest) {}

    public static function fromModel(VideoRequest $videoRequest): self
    {
        return new self([
            'id' => $videoRequest->id,
            'hospital_id' => $videoRequest->hospital_id,
            'beauty_id' => $videoRequest->beauty_id,
            'doctor_id' => $videoRequest->doctor_id,
            'expert_id' => $videoRequest->expert_id,
            'submitted_by_partner_id' => $videoRequest->submitted_by_partner_id,
            'title' => $videoRequest->title,
            'description' => $videoRequest->description,
            'is_usage_consented' => (bool) $videoRequest->is_usage_consented,
            'source_video_file' => self::formatMedia($videoRequest->sourceVideo),
            'source_thumbnail_file' => self::formatMedia($videoRequest->sourceThumbnail),
            'duration_seconds' => (int) $videoRequest->duration_seconds,
            'requested_publish_start_at' => $videoRequest->requested_publish_start_at?->toISOString(),
            'requested_publish_end_at' => $videoRequest->requested_publish_end_at?->toISOString(),
            'is_publish_period_unlimited' => (bool) $videoRequest->is_publish_period_unlimited,
            'review_status' => $videoRequest->review_status,
            'reviewed_by_staff_id' => $videoRequest->reviewed_by_staff_id,
            'reviewed_at' => $videoRequest->reviewed_at?->toISOString(),
            'reject_reason' => $videoRequest->reject_reason,
            'reject_reason_detail' => $videoRequest->reject_reason_detail,
            'created_at' => $videoRequest->created_at?->toISOString(),
            'updated_at' => $videoRequest->updated_at?->toISOString(),
            'deleted_at' => $videoRequest->deleted_at?->toISOString(),
        ]);
    }

    public function toArray(): array
    {
        return $this->videoRequest;
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
