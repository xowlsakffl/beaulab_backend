<?php

namespace App\Domains\HospitalVideo\Queries\Staff;

use App\Domains\HospitalVideo\Models\HospitalVideo;

final class HospitalVideoCreateForStaffQuery
{
    public function create(array $payload): HospitalVideo
    {
        return HospitalVideo::create([
            'hospital_id' => $payload['hospital_id'],
            'doctor_id' => $payload['doctor_id'] ?? null,
            'title' => $payload['title'],
            'description' => $payload['description'] ?? null,
            'distribution_channel' => $payload['distribution_channel'] ?? HospitalVideo::DISTRIBUTION_CHANNEL_YOUTUBE,
            'external_video_id' => $payload['external_video_id'] ?? null,
            'external_video_url' => $payload['external_video_url'] ?? null,
            'duration_seconds' => (int) ($payload['duration_seconds'] ?? 0),
            'status' => $payload['status'] ?? HospitalVideo::STATUS_ACTIVE,
            'published_at' => $payload['published_at'] ?? now(),
            'publish_start_at' => $payload['publish_start_at'] ?? null,
            'publish_end_at' => $payload['publish_end_at'] ?? null,
            'is_publish_period_unlimited' => (bool) ($payload['is_publish_period_unlimited'] ?? false),
        ]);
    }

    public function updateThumbnailMediaId(HospitalVideo $video, int $thumbnailMediaId): HospitalVideo
    {
        $video->thumbnail_media_id = $thumbnailMediaId;

        if ($video->isDirty('thumbnail_media_id')) {
            $video->save();
        }

        return $video->fresh();
    }
}
