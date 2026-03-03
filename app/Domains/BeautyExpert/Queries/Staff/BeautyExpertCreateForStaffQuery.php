<?php

namespace App\Domains\BeautyExpert\Queries\Staff;

use App\Domains\BeautyExpert\Models\BeautyExpert;

final class BeautyExpertCreateForStaffQuery
{
    public function create(array $data): BeautyExpert
    {
        return BeautyExpert::create([
            'beauty_id' => $data['beauty_id'],
            'sort_order' => $data['sort_order'] ?? 0,
            'name' => $data['name'],
            'gender' => $data['gender'] ?? null,
            'position' => $data['position'] ?? null,
            'career_started_at' => $data['career_started_at'] ?? null,
            'educations' => $data['educations'] ?? null,
            'careers' => $data['careers'] ?? null,
            'etc_contents' => $data['etc_contents'] ?? null,
            'status' => 'SUSPENDED',
            'allow_status' => 'PENDING',
        ]);
    }
}
