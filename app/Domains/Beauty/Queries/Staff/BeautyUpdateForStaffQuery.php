<?php

namespace App\Domains\Beauty\Queries\Staff;

use App\Domains\Beauty\Models\Beauty;

final class BeautyUpdateForStaffQuery
{
    /**
     * 병원 정보 업데이트 (Staff 전용)
     * - name 제외 (Request에서 안 받으니 여기에도 없음)
     * - 변경사항 없으면 save() 생략
     */
    public function update(Beauty $beauty, array $filter): Beauty
    {
        $beauty->fill([
            'description' => array_key_exists('description', $filter) ? $filter['description'] : $beauty->description,
            'address' => array_key_exists('address', $filter) ? $filter['address'] : $beauty->address,
            'address_detail' => array_key_exists('address_detail', $filter) ? $filter['address_detail'] : $beauty->address_detail,
            'latitude' => array_key_exists('latitude', $filter)
                ? ($filter['latitude'] !== null ? (string) $filter['latitude'] : null)
                : $beauty->latitude,
            'longitude' => array_key_exists('longitude', $filter)
                ? ($filter['longitude'] !== null ? (string) $filter['longitude'] : null)
                : $beauty->longitude,
            'tel' => array_key_exists('tel', $filter) ? $filter['tel'] : $beauty->tel,
            'email' => array_key_exists('email', $filter) ? $filter['email'] : $beauty->email,
            'consulting_hours' => array_key_exists('consulting_hours', $filter) ? $filter['consulting_hours'] : $beauty->consulting_hours,
            'direction' => array_key_exists('direction', $filter) ? $filter['direction'] : $beauty->direction,
        ]);

        if ($beauty->isDirty()) {
            $beauty->save();
        }

        return $beauty->fresh();
    }
}
