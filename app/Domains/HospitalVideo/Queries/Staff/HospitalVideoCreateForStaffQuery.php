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
            'manager_staff_id' => $payload['manager_staff_id'] ?? null,
            'title' => $payload['title'],
            'description' => $payload['description'] ?? null,
            'external_video_url' => $payload['external_video_url'],
            'duration_seconds' => (int) ($payload['duration_seconds'] ?? 0),
        ]);
    }
}
