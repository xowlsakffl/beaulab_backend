<?php

namespace App\Modules\Staff\Http\Requests\HospitalVideo;

use Illuminate\Foundation\Http\FormRequest;

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
