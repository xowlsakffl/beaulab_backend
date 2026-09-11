<?php

namespace App\Modules\Staff\Http\Requests\HospitalPromotion;

use App\Domains\HospitalPromotion\Models\HospitalPromotion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalPromotionListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $status = $this->input('status');
        if (is_string($status)) {
            $status = trim($status) === '' ? null : array_map('trim', explode(',', $status));
        }
        $this->merge(['status' => $status]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'array', 'max:2'],
            'status.*' => ['required', 'string', 'distinct', Rule::in(HospitalPromotion::STATUSES)],
            'start_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31', ...($this->filled('start_date') ? ['after_or_equal:start_date'] : [])],
            'page' => ['nullable', 'integer', 'min:1'],
            'left_page' => ['nullable', 'integer', 'min:1'],
            'right_page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
