<?php

namespace App\Modules\Staff\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

final class DoctorHospitalOptionListForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'q' => $validated['q'] ?? null,
            'per_page' => (int) ($validated['per_page'] ?? 10),
        ];
    }

    public function attributes(): array
    {
        return [
            'q' => '검색어',
            'per_page' => '조회 개수',
        ];
    }
}
