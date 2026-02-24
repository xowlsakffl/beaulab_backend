<?php

namespace App\Domains\Doctor\Queries\Staff;

use App\Domains\Doctor\Models\Doctor;

final class DoctorCreateForStaffQuery
{
    public function create(array $data): Doctor
    {
        return Doctor::create([
            'hospital_id' => $data['hospital_id'],
            'sort_order' => $data['sort_order'] ?? 0,
            'name' => $data['name'],
            'gender' => $data['gender'] ?? null,
            'position' => $data['position'] ?? null,
            'career_started_at' => $data['career_started_at'] ?? null,
            'license_number' => $data['license_number'] ?? null,
            'is_specialist' => (bool) ($data['is_specialist'] ?? false),
            'educations' => $data['educations'] ?? null,
            'careers' => $data['careers'] ?? null,
            'etc_contents' => $data['etc_contents'] ?? null,
            'status' => 'SUSPENDED',
            'allow_status' => 'PENDING',
        ]);
    }
}
