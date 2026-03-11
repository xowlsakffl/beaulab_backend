<?php

namespace App\Domains\HospitalVideo\Queries\Staff;

use App\Domains\HospitalVideo\Models\HospitalVideo;

final class HospitalVideoCreateForStaffQuery
{
    public function create(array $payload): HospitalVideo
    {
        $data = [
            'hospital_id' => $payload['hospital_id'],
            'doctor_id' => $payload['doctor_id'] ?? null,
            'title' => $payload['title'],
            'description' => $payload['description'] ?? null,
            'distribution_channel' => $payload['distribution_channel'] ?? HospitalVideo::DISTRIBUTION_CHANNEL_YOUTUBE,
            'external_video_id' => $payload['external_video_id'] ?? null,
            'external_video_url' => $payload['external_video_url'] ?? null,
            'duration_seconds' => (int) ($payload['duration_seconds'] ?? 0),
            'status' => $payload['status'] ?? HospitalVideo::STATUS_ACTIVE,
            'publish_start_at' => $payload['publish_start_at'] ?? null,
            'publish_end_at' => $payload['publish_end_at'] ?? null,
            'is_publish_period_unlimited' => (bool) ($payload['is_publish_period_unlimited'] ?? false),
        ];

        if (array_key_exists('allow_status', $payload) && $payload['allow_status'] !== null) {
            $data['allow_status'] = $payload['allow_status'];
        }

        return HospitalVideo::create($data);
    }

}
