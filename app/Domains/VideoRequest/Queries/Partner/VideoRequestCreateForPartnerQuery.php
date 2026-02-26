<?php

namespace App\Domains\VideoRequest\Queries\Partner;

use App\Domains\Partner\Models\AccountPartner;
use App\Domains\VideoRequest\Models\VideoRequest;

final class VideoRequestCreateForPartnerQuery
{
    public function create(array $data, AccountPartner $actor): VideoRequest
    {
        return VideoRequest::create([
            'hospital_id' => $data['hospital_id'] ?? null,
            'beauty_id' => $data['beauty_id'] ?? null,
            'doctor_id' => $data['doctor_id'] ?? null,
            'expert_id' => $data['expert_id'] ?? null,
            'submitted_by_partner_id' => $actor->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'is_usage_consented' => (bool) $data['is_usage_consented'],
            'duration_seconds' => (int) $data['duration_seconds'],
            'requested_publish_start_at' => $data['requested_publish_start_at'] ?? null,
            'requested_publish_end_at' => $data['requested_publish_end_at'] ?? null,
            'is_publish_period_unlimited' => (bool) ($data['is_publish_period_unlimited'] ?? false),
            'review_status' => VideoRequest::REVIEW_STATUS_PENDING,
            'reviewed_by_staff_id' => null,
            'reviewed_at' => null,
            'reject_reason' => null,
            'reject_reason_detail' => null,
        ]);
    }
}
