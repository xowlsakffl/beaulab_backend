<?php

namespace App\Modules\Staff\Http\Requests\HospitalEventAd;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalEventAdGetForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'operation_histories_page' => ['nullable', 'integer', 'min:1'],
            'operation_histories_per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'operation_histories_page' => (int) ($validated['operation_histories_page'] ?? 1),
            'operation_histories_per_page' => (int) ($validated['operation_histories_per_page'] ?? 10),
        ];
    }
}
