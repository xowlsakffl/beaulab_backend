<?php

namespace App\Domains\Hospital\Queries\Staff;

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
            'description' => array_key_exists('description', $filter) ? $filter['description'] : $hospital->description,
            'address' => array_key_exists('address', $filter) ? $filter['address'] : $hospital->address,
            'address_detail' => array_key_exists('address_detail', $filter) ? $filter['address_detail'] : $hospital->address_detail,
            'latitude' => array_key_exists('latitude', $filter)
                ? ($filter['latitude'] !== null ? (string) $filter['latitude'] : null)
                : $hospital->latitude,
            'longitude' => array_key_exists('longitude', $filter)
                ? ($filter['longitude'] !== null ? (string) $filter['longitude'] : null)
                : $hospital->longitude,
            'tel' => array_key_exists('tel', $filter) ? $filter['tel'] : $hospital->tel,
            'email' => array_key_exists('email', $filter) ? $filter['email'] : $hospital->email,
            'allow_status' => array_key_exists('allow_status', $filter) ? $filter['allow_status'] : $hospital->allow_status,
            'status' => array_key_exists('status', $filter) ? $filter['status'] : $hospital->status,
            'consulting_hours' => array_key_exists('consulting_hours', $filter) ? $filter['consulting_hours'] : $hospital->consulting_hours,
            'direction' => array_key_exists('direction', $filter) ? $filter['direction'] : $hospital->direction,
        ]);

        if ($hospital->isDirty()) {
            $hospital->save();
        }

        return $hospital->fresh();
    }
}
