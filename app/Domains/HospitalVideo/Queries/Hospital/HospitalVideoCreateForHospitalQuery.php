<?php

namespace App\Domains\HospitalVideo\Queries\Hospital;

use App\Domains\HospitalVideo\Models\HospitalVideo;

/**
 * HospitalVideoCreateForHospitalQuery 역할 정의.
 * 병원 동영상 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
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
            'allow_status' => HospitalVideo::ALLOW_SUBMITTED,
            'publish_start_at' => $payload['publish_start_at'] ?? null,
            'publish_end_at' => $payload['publish_end_at'] ?? null,
            'is_publish_period_unlimited' => false,
        ]);
    }
}
