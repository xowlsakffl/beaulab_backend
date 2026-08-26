<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\Hospital;

use App\Domains\Hospital\Models\HospitalStatusChangeRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalStatusChangeRequestProcessForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => is_string($this->input('status'))
                ? strtoupper(trim((string) $this->input('status')))
                : $this->input('status'),
            'rejection_reason' => is_string($this->input('rejection_reason'))
                ? trim((string) $this->input('rejection_reason'))
                : $this->input('rejection_reason'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(HospitalStatusChangeRequest::decisions())],
            'rejection_reason' => [
                'nullable',
                'string',
                'max:500',
                Rule::requiredIf(fn (): bool => $this->input('status') === HospitalStatusChangeRequest::STATUS_REJECTED),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => '결재 상태',
            'rejection_reason' => '반려 사유',
        ];
    }
}
