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
            'hospital_status' => $payload['hospital_status'] ?? HospitalVideo::HOSPITAL_STATUS_PUBLIC,
            'admin_status' => $payload['admin_status'] ?? HospitalVideo::ADMIN_STATUS_NORMAL,
            'view_count' => (int) ($payload['view_count'] ?? 0),
            'like_count' => (int) ($payload['like_count'] ?? 0),
        ]);
    }
}
