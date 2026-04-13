<?php

namespace App\Domains\Hospital\Queries\Staff;

use App\Domains\Hospital\Models\Hospital;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * HospitalCreateForStaffQuery 역할 정의.
 * 병원 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class HospitalCreateForStaffQuery
{
    /**
     * 뷰랩 직원 전용 병원 생성
     */
    public function create(array $data): Hospital
    {
        return Hospital::create([
            'name'             => $data['name'],

            'description'      => $data['description'] ?? null,

            'address'          => $data['address'] ?? null,
            'address_detail'   => $data['address_detail'] ?? null,

            'latitude'         => $data['latitude'] ?? null,
            'longitude'        => $data['longitude'] ?? null,

            'tel'              => $data['tel'] ?? null,
            'email'            => $data['email'] ?? null,

            'consulting_hours' => $data['consulting_hours'] ?? null,
            'direction'        => $data['direction'] ?? null,

            // 생성 시 정책 기본값
            'view_count'       => 0,
            'allow_status'     => $data['allow_status'] ?? Hospital::ALLOW_PENDING,
            'status'           => $data['status'] ?? Hospital::STATUS_SUSPENDED,
        ]);
    }
}
