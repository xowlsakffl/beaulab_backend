<?php


namespace App\Modules\Admin\Http\Requests\Hospital;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalCreateForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 필수
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('hospitals', 'name')
                    ->whereNull('deleted_at'),
            ],

            // 소개/텍스트
            'description' => ['nullable', 'string', 'max:5000'],
            'consulting_hours' => ['nullable', 'string', 'max:5000'],
            'direction' => ['nullable', 'string', 'max:5000'],

            // 주소
            'address' => ['nullable', 'string', 'max:255'],
            'address_detail' => ['nullable', 'string', 'max:255'],

            // 좌표
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            // 연락처
            'tel' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+\-().\s]{6,50}$/'],
            'email' => ['nullable', 'email:rfc,dns', 'max:255'],

            // 파일
            'logo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],// 5MB
            'gallery' => ['nullable', 'array', 'max:12'],
            'gallery.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'], // 8MB
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '병원명',
            'address' => '주소',
            'address_detail' => '상세 주소',
            'tel' => '대표 번호',
            'email' => '대표 이메일',
            'logo' => '로고',
            'gallery' => '대표/내부 이미지',
            'gallery.*' => '대표/내부 이미지',
        ];
    }


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

        if (isset($data['latitude']) && $data['latitude'] !== null) {
            $data['latitude'] = (string) $data['latitude'];
        }

        if (isset($data['longitude']) && $data['longitude'] !== null) {
            $data['longitude'] = (string) $data['longitude'];
        }

        if (!empty($data['email'])) {
            $data['email'] = mb_strtolower($data['email']);
        }

        unset($data['logo'], $data['gallery']);

        return $data;
    }
}
