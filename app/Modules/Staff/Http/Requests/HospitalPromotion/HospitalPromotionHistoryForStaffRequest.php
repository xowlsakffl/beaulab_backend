<?php

namespace App\Modules\Staff\Http\Requests\HospitalPromotion;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalPromotionHistoryForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'operation_histories_page' => ['nullable', 'integer', 'min:1'],
            'operation_histories_per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
