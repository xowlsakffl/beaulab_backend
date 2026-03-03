<?php

namespace App\Domains\HospitalVideoRequest\Queries\Hospital;

use App\Domains\HospitalVideoRequest\Models\HospitalVideoRequest;

final class HospitalVideoRequestUpdateForHospitalQuery
{
    public function update(HospitalVideoRequest $videoRequest, array $payload): HospitalVideoRequest
    {
        $videoRequest->fill([
            'hospital_id' => array_key_exists('hospital_id', $payload) ? $payload['hospital_id'] : $videoRequest->hospital_id,
            'doctor_id' => array_key_exists('doctor_id', $payload) ? $payload['doctor_id'] : $videoRequest->doctor_id,
            'title' => array_key_exists('title', $payload) ? $payload['title'] : $videoRequest->title,
            'description' => array_key_exists('description', $payload) ? $payload['description'] : $videoRequest->description,
            'is_usage_consented' => array_key_exists('is_usage_consented', $payload) ? (bool) $payload['is_usage_consented'] : $videoRequest->is_usage_consented,
            'duration_seconds' => array_key_exists('duration_seconds', $payload) ? (int) $payload['duration_seconds'] : $videoRequest->duration_seconds,
            'requested_publish_start_at' => array_key_exists('requested_publish_start_at', $payload) ? $payload['requested_publish_start_at'] : $videoRequest->requested_publish_start_at,
            'requested_publish_end_at' => array_key_exists('requested_publish_end_at', $payload) ? $payload['requested_publish_end_at'] : $videoRequest->requested_publish_end_at,
            'is_publish_period_unlimited' => array_key_exists('is_publish_period_unlimited', $payload) ? (bool) $payload['is_publish_period_unlimited'] : $videoRequest->is_publish_period_unlimited,
        ]);

        if ($videoRequest->isDirty()) {
            $videoRequest->save();
        }

        return $videoRequest->fresh();
    }
}
