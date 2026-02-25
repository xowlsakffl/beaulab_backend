<?php

namespace App\Domains\Expert\Queries\Staff;

use App\Domains\Expert\Models\Expert;

final class ExpertCreateForStaffQuery
{
    public function create(array $data): Expert
    {
        return Expert::create([
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
