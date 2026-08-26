<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\Hospital;

use App\Domains\Hospital\Models\Hospital;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalStatusChangeRequestCreateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'target_status' => is_string($this->input('target_status'))
                ? strtoupper(trim((string) $this->input('target_status')))
                : $this->input('target_status'),
            'reason' => is_string($this->input('reason'))
                ? trim((string) $this->input('reason'))
                : $this->input('reason'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_status' => ['required', Rule::in([
                Hospital::STATUS_ACTIVE,
                Hospital::STATUS_SUSPENDED,
            ])],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'target_status' => '요청 운영상태',
            'reason' => '운영중지 요청사유',
        ];
    }
}
