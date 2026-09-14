<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalVideo;

use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalVideo\Queries\Staff\HospitalVideoConsentListForStaffQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalVideoConsentListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['department', 'status'] as $key) {
            $value = $this->input($key);
            if (is_string($value)) {
                $this->merge([$key => trim($value) === '' ? null : array_map('trim', explode(',', $value))]);
            }
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'array', 'max:7'],
            'department.*' => ['required', 'string', 'distinct', Rule::in(Hospital::departments())],
            'status' => ['nullable', 'array', 'max:3'],
            'status.*' => ['required', 'string', 'distinct', Rule::in([Hospital::STATUS_ACTIVE, Hospital::STATUS_SUSPENDED, Hospital::STATUS_WITHDRAWN])],
            'start_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31', ...($this->filled('start_date') ? ['after_or_equal:start_date'] : [])],
            'sort' => ['nullable', Rule::in(HospitalVideoConsentListForStaffQuery::SORT_FIELDS)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
