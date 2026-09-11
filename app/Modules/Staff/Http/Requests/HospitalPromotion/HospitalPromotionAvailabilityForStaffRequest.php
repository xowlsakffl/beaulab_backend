<?php

namespace App\Modules\Staff\Http\Requests\HospitalPromotion;

use App\Domains\HospitalPromotion\Models\HospitalPromotion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalPromotionAvailabilityForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'side' => ['required', Rule::in(HospitalPromotion::SIDES)],
            'slot' => ['required', 'integer', 'between:1,3'],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:9999-12-31'],
            'exclude_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
