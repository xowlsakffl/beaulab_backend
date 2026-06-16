<?php

namespace App\Modules\Staff\Http\Requests\Hospital;

use App\Domains\Hospital\Models\Hospital;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalStatusUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('status'))) {
            $this->merge([
                'status' => strtoupper(trim((string) $this->input('status'))),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                Hospital::STATUS_ACTIVE,
                Hospital::STATUS_SUSPENDED,
            ])],
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => '병의원 상태',
        ];
    }
}
