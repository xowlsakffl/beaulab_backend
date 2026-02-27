<?php

namespace App\Domains\VideoRequest\Queries\Partner;

use App\Domains\VideoRequest\Models\VideoRequest;

final class VideoRequestUpdateForPartnerQuery
{
    public function update(VideoRequest $videoRequest, array $payload): VideoRequest
    {
        $videoRequest->fill([
            'hospital_id' => array_key_exists('hospital_id', $payload) ? $payload['hospital_id'] : $videoRequest->hospital_id,
            'beauty_id' => array_key_exists('beauty_id', $payload) ? $payload['beauty_id'] : $videoRequest->beauty_id,
            'doctor_id' => array_key_exists('doctor_id', $payload) ? $payload['doctor_id'] : $videoRequest->doctor_id,
            'expert_id' => array_key_exists('expert_id', $payload) ? $payload['expert_id'] : $videoRequest->expert_id,
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
