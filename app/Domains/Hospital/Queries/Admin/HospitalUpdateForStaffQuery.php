<?php

namespace App\Domains\Hospital\Queries\Admin;

use App\Domains\Hospital\Models\Hospital;

final class HospitalUpdateForStaffQuery
{
    /**
     * 병원 정보 업데이트 (Staff 전용)
     * - name 제외 (Request에서 안 받으니 여기에도 없음)
     * - 변경사항 없으면 save() 생략
     */
    public function update(Hospital $hospital, array $filter): Hospital
    {
        $hospital->fill([
            'description'      => $filter['description'] ?? $hospital->description,
            'address'          => $filter['address'] ?? $hospital->address,
            'address_detail'   => $filter['address_detail'] ?? $hospital->address_detail,
            'latitude'         => array_key_exists('latitude', $filter) ? $filter['latitude'] : $hospital->latitude,
            'longitude'        => array_key_exists('longitude', $filter) ? $filter['longitude'] : $hospital->longitude,
            'tel'              => $filter['tel'] ?? $hospital->tel,
            'email'            => array_key_exists('email', $filter) ? $filter['email'] : $hospital->email,
            'consulting_hours' => $filter['consulting_hours'] ?? $hospital->consulting_hours,
            'direction'        => $filter['direction'] ?? $hospital->direction,
        ]);

        if ($hospital->isDirty()) {
            $hospital->save();
        }

        return $hospital->fresh();
    }
}
