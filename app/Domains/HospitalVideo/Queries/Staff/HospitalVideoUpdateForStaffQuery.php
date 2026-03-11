<?php

namespace App\Domains\HospitalVideo\Queries\Staff;

use App\Domains\HospitalVideo\Models\HospitalVideo;

final class HospitalVideoUpdateForStaffQuery
{
    public function update(HospitalVideo $video, array $payload): HospitalVideo
    {
        $video->fill([
            'hospital_id' => array_key_exists('hospital_id', $payload) ? $payload['hospital_id'] : $video->hospital_id,
            'doctor_id' => array_key_exists('doctor_id', $payload) ? $payload['doctor_id'] : $video->doctor_id,
            'title' => array_key_exists('title', $payload) ? $payload['title'] : $video->title,
            'description' => array_key_exists('description', $payload) ? $payload['description'] : $video->description,
            'distribution_channel' => array_key_exists('distribution_channel', $payload) ? $payload['distribution_channel'] : $video->distribution_channel,
            'external_video_id' => array_key_exists('external_video_id', $payload) ? $payload['external_video_id'] : $video->external_video_id,
            'external_video_url' => array_key_exists('external_video_url', $payload) ? $payload['external_video_url'] : $video->external_video_url,
            'duration_seconds' => array_key_exists('duration_seconds', $payload) ? (int) $payload['duration_seconds'] : $video->duration_seconds,
            'status' => array_key_exists('status', $payload) ? $payload['status'] : $video->status,
            'allow_status' => array_key_exists('allow_status', $payload) ? $payload['allow_status'] : $video->allow_status,
            'publish_start_at' => array_key_exists('publish_start_at', $payload) ? $payload['publish_start_at'] : $video->publish_start_at,
            'publish_end_at' => array_key_exists('publish_end_at', $payload) ? $payload['publish_end_at'] : $video->publish_end_at,
            'is_publish_period_unlimited' => array_key_exists('is_publish_period_unlimited', $payload) ? (bool) $payload['is_publish_period_unlimited'] : $video->is_publish_period_unlimited,
        ]);

        if ($video->isDirty()) {
            $video->save();
        }

        return $video->fresh();
    }

}
