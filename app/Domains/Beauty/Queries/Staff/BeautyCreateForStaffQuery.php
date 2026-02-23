<?php

namespace App\Domains\Beauty\Queries\Staff;

use App\Domains\Beauty\Models\Beauty;

final class BeautyCreateForStaffQuery
{
    /**
     * 뷰랩 직원 전용 병원 생성
     */
    public function create(array $data): Beauty
    {
        return Beauty::create([
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
            'allow_status'     => 'PENDING',
            'status'           => 'SUSPENDED',
        ]);
    }
}
