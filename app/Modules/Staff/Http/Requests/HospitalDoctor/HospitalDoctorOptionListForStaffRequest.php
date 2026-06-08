<?php

namespace App\Modules\Staff\Http\Requests\HospitalDoctor;

use Illuminate\Foundation\Http\FormRequest;

/**
 * HospitalDoctorOptionListForStaffRequest 역할 정의.
 * 의료진명 자동완성 조회 요청을 검증하고 필터로 정규화한다.
 */
final class HospitalDoctorOptionListForStaffRequest extends FormRequest
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
            'per_page' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'hospital_id' => (int) $validated['hospital_id'],
            'q' => $validated['q'] ?? null,
            'per_page' => (int) ($validated['per_page'] ?? 3),
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
