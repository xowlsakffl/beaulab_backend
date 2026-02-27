<?php

namespace App\Domains\VideoRequest\Queries\Partner;

use App\Domains\VideoRequest\Models\VideoRequest;

final class VideoRequestCreateForPartnerQuery
{
    public function create(array $payload): VideoRequest
    {
        return VideoRequest::create([
            'hospital_id' => $payload['hospital_id'] ?? null,
            'beauty_id' => $payload['beauty_id'] ?? null,
            'doctor_id' => $payload['doctor_id'] ?? null,
            'expert_id' => $payload['expert_id'] ?? null,
            'submitted_by_partner_id' => $payload['submitted_by_partner_id'],
            'title' => $payload['title'],
            'description' => $payload['description'] ?? null,
            'is_usage_consented' => (bool) $payload['is_usage_consented'],
            'duration_seconds' => (int) $payload['duration_seconds'],
            'requested_publish_start_at' => $payload['requested_publish_start_at'] ?? null,
            'requested_publish_end_at' => $payload['requested_publish_end_at'] ?? null,
            'is_publish_period_unlimited' => (bool) $payload['is_publish_period_unlimited'],
            'review_status' => VideoRequest::REVIEW_STATUS_PENDING,
        ]);
    }
}
