<?php

namespace App\Modules\Staff\Http\Requests\HospitalVideo;

use Illuminate\Foundation\Http\FormRequest;

/**
 * HospitalVideoDoctorOptionListForStaffRequest 역할 정의.
 * 병원 동영상 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class HospitalVideoDoctorOptionListForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hospital_id' => ['required', 'integer', 'exists:hospitals,id'],
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'hospital_id' => (int) $validated['hospital_id'],
            'q' => $validated['q'] ?? null,
            'per_page' => (int) ($validated['per_page'] ?? 20),
        ];
    }

    public function attributes(): array
    {
        return [
            'hospital_id' => '병의원',
            'q' => '검색어',
            'per_page' => '조회 개수',
        ];
    }
}
