<?php

namespace App\Modules\Staff\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

final class DoctorUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach (['gender', 'position', 'career_started_at', 'license_number'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }

        foreach (['educations', 'careers', 'etc_contents'] as $key) {
            if (array_key_exists($key, $data) && is_string($data[$key])) {
                $decoded = json_decode($data[$key], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data[$key] = $decoded;
                }
            }
        }

        $this->replace($data);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'name' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:50'],
            'career_started_at' => ['nullable', 'date'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'is_specialist' => ['nullable', 'boolean'],
            'educations' => ['nullable', 'array'],
            'careers' => ['nullable', 'array'],
            'etc_contents' => ['nullable', 'array'],
            'status' => ['nullable', 'in:ACTIVE,SUSPENDED,WITHDRAWN'],
            'allow_status' => ['nullable', 'in:PENDING,APPROVED,REJECTED'],

            'profile_image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'license_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'specialist_certificate_image' => ['nullable', 'array', 'max:12'],
            'specialist_certificate_image.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'graduation_certificate_image' => ['nullable', 'array', 'max:12'],
            'graduation_certificate_image.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'etc_certificate_image' => ['nullable', 'array', 'max:12'],
            'etc_certificate_image.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ];
    }

    public function attributes(): array
    {
        return [
            'hospital_id' => "병원 ID",
            'sort_order' => "정렬 순서",
            'name' => '의사명',
            'gender' => '성별',
            'position' => "직책",
            'career_started_at' => '경력 시작일',
            'license_number' => '면허증 번호',
            'is_specialist' => "전문의 여부",
            'educations' => '학력 사항',
            'careers' => '경력 사항',
            'etc_contents' => '기타 사항',

            'profile_image' => '프로필 이미지',
            'license_image' => '면허증 이미지',

            'specialist_certificate_image' => '전문의 면허증 이미지',
            'specialist_certificate_image.*' => '전문의 면허증 이미지',
            'graduation_certificate_image' => '학력 증명서 이미지',
            'graduation_certificate_image.*' => '학력 증명서 이미지',
            'etc_certificate_image' => '기타 증명서 이미지',
            'etc_certificate_image.*' => '기타 증명서 이미지',
        ];
    }
}
