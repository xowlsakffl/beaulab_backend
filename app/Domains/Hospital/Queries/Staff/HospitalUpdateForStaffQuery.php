<?php

namespace App\Domains\Hospital\Queries\Staff;

use App\Domains\Hospital\Models\Hospital;

/**
 * HospitalUpdateForStaffQuery 역할 정의.
 * 병원 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
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
            'department' => array_key_exists('department', $filter) ? $filter['department'] : $hospital->department,
            'description' => array_key_exists('description', $filter) ? $filter['description'] : $hospital->description,
            'youtube_link' => array_key_exists('youtube_link', $filter) ? $filter['youtube_link'] : $hospital->youtube_link,
            'address' => array_key_exists('address', $filter) ? $filter['address'] : $hospital->address,
            'address_detail' => array_key_exists('address_detail', $filter) ? $filter['address_detail'] : $hospital->address_detail,
            'latitude' => array_key_exists('latitude', $filter)
                ? ($filter['latitude'] !== null ? (string) $filter['latitude'] : null)
                : $hospital->latitude,
            'longitude' => array_key_exists('longitude', $filter)
                ? ($filter['longitude'] !== null ? (string) $filter['longitude'] : null)
                : $hospital->longitude,
            'tel' => array_key_exists('tel', $filter) ? $filter['tel'] : $hospital->tel,
            'ad_reception_phone_1' => array_key_exists('ad_reception_phone_1', $filter) ? $filter['ad_reception_phone_1'] : $hospital->ad_reception_phone_1,
            'ad_reception_phone_2' => array_key_exists('ad_reception_phone_2', $filter) ? $filter['ad_reception_phone_2'] : $hospital->ad_reception_phone_2,
            'ad_reception_phone_3' => array_key_exists('ad_reception_phone_3', $filter) ? $filter['ad_reception_phone_3'] : $hospital->ad_reception_phone_3,
            'allow_status' => array_key_exists('allow_status', $filter) ? $filter['allow_status'] : $hospital->allow_status,
            'status' => array_key_exists('status', $filter) ? $filter['status'] : $hospital->status,
            'consulting_hours' => array_key_exists('consulting_hours', $filter) ? $filter['consulting_hours'] : $hospital->consulting_hours,
            'operation_hours' => array_key_exists('operation_hours', $filter) ? $filter['operation_hours'] : $hospital->operation_hours,
            'direction' => array_key_exists('direction', $filter) ? $filter['direction'] : $hospital->direction,
        ]);

        if ($hospital->isDirty()) {
            $hospital->save();
        }

        return $hospital->fresh();
    }
}
