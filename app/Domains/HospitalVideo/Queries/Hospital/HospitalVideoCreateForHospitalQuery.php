<?php

namespace App\Domains\HospitalVideo\Queries\Hospital;

use App\Domains\HospitalVideo\Models\HospitalVideo;

final class HospitalVideoCreateForHospitalQuery
{
    public function create(array $payload): HospitalVideo
    {
        return HospitalVideo::create([
            'hospital_id' => $payload['hospital_id'],
            'doctor_id' => $payload['doctor_id'] ?? null,
            'submitted_by_account_id' => $payload['submitted_by_account_id'],
            'title' => $payload['title'],
            'description' => $payload['description'] ?? null,
            'is_usage_consented' => true,
            'distribution_channel' => $payload['distribution_channel'] ?? HospitalVideo::DISTRIBUTION_CHANNEL_YOUTUBE_APP,
            'status' => HospitalVideo::STATUS_INACTIVE,
            'allow_status' => HospitalVideo::ALLOW_STATUS_SUBMITTED,
            'publish_start_at' => $payload['publish_start_at'] ?? null,
            'publish_end_at' => $payload['publish_end_at'] ?? null,
            'is_publish_period_unlimited' => false,
        ]);
    }
}
