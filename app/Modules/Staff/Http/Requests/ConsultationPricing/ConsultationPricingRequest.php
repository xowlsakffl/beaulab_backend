<?php

namespace App\Modules\Staff\Http\Requests\ConsultationPricing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ConsultationPricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['EVENT', 'REMOTE', 'REAL_MODEL'])],
            'division' => ['sometimes', Rule::in(['SURGERY', 'TREATMENT', 'PROMOTION'])],
            'category_id' => ['sometimes', 'integer', 'min:0'],
            'lock_version' => [$this->isMethod('GET') ? 'sometimes' : 'required', 'integer', 'min:0'],
            'pending_id' => [$this->isMethod('DELETE') ? 'required' : 'sometimes', 'integer', 'min:1'],
            'mode' => [$this->isMethod('PUT') ? 'required' : 'sometimes', Rule::in(['DEFAULT', 'CUSTOM'])],
            'ranges' => ['nullable', 'array', 'list', 'max:100'],
            'ranges.*.upper' => ['present', 'nullable', 'integer', 'min:1', 'max:2000000000'],
            'ranges.*.price' => ['required', 'integer', 'min:0', 'max:2000000000'],
            'fixed_price' => ['nullable', 'integer', 'min:0', 'max:2000000000'],
            'effective_at' => ['nullable', 'date_format:Y-m-d\TH:i:sP'],
        ];
    }
}
