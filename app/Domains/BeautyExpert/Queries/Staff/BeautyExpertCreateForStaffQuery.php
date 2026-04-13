<?php

namespace App\Domains\BeautyExpert\Queries\Staff;

use App\Domains\BeautyExpert\Models\BeautyExpert;

/**
 * BeautyExpertCreateForStaffQuery 역할 정의.
 * 뷰티 전문가 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
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
