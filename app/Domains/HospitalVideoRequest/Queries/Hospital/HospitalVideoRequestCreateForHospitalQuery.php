<?php

namespace App\Domains\HospitalVideoRequest\Queries\Hospital;

use App\Domains\HospitalVideoRequest\Models\HospitalVideoRequest;

final class HospitalVideoRequestCreateForHospitalQuery
{
    public function create(array $payload): HospitalVideoRequest
    {
        return HospitalVideoRequest::create([
            'hospital_id' => $payload['hospital_id'] ?? null,
            'doctor_id' => $payload['doctor_id'] ?? null,
            'submitted_by_account_id' => $payload['submitted_by_account_id'],
            'title' => $payload['title'],
            'description' => $payload['description'] ?? null,
            'is_usage_consented' => (bool) $payload['is_usage_consented'],
            'duration_seconds' => (int) $payload['duration_seconds'],
            'requested_publish_start_at' => $payload['requested_publish_start_at'] ?? null,
            'requested_publish_end_at' => $payload['requested_publish_end_at'] ?? null,
            'is_publish_period_unlimited' => (bool) $payload['is_publish_period_unlimited'],
            'review_status' => HospitalVideoRequest::REVIEW_STATUS_APPLYING,
        ]);
    }
}
