<?php

namespace App\Modules\Staff\Http\Requests\Hospital;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalUpdateForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // name은 변경 불가(수정 입력에서 제외)

            'description' => ['nullable', 'string', 'max:5000'],
            'consulting_hours' => ['nullable', 'string', 'max:5000'],
            'direction' => ['nullable', 'string', 'max:5000'],

            'address' => ['nullable', 'string', 'max:255'],
            'address_detail' => ['nullable', 'string', 'max:255'],

            // 좌표 (DB는 string이지만 입력은 숫자 형태를 강제)
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            'tel' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+\-().\s]{6,50}$/'],
            'email' => ['nullable', 'email:rfc,dns', 'max:255'],
        ];
    }

    /**
     * 업데이트 payload 정리
     * - "" => null 정규화
     * - 좌표 numeric → string 캐스팅
     * - email 소문자 정규화
     */
    public function filters(): array
    {
        $data = $this->validated();

        $nullableKeys = [
            'description', 'address', 'address_detail', 'latitude', 'longitude',
            'tel', 'email', 'consulting_hours', 'direction',
        ];

        foreach ($nullableKeys as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            if ($data[$key] === '') {
                $data[$key] = null;
            }
        }

        if (array_key_exists('latitude', $data)) {
            $data['latitude'] = $data['latitude'] !== null ? (string) $data['latitude'] : null;
        }

        if (array_key_exists('longitude', $data)) {
            $data['longitude'] = $data['longitude'] !== null ? (string) $data['longitude'] : null;
        }

        if (!empty($data['email'])) {
            $data['email'] = mb_strtolower($data['email']);
        }

        return $data;
    }
}
