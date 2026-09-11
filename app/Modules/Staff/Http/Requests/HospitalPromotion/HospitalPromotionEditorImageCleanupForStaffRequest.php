<?php

namespace App\Modules\Staff\Http\Requests\HospitalPromotion;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalPromotionEditorImageCleanupForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paths' => ['nullable', 'array', 'max:100'],
            'paths.*' => ['required', 'string', 'max:1000'],
            'urls' => ['nullable', 'array', 'max:100'],
            'urls.*' => ['required', 'string', 'max:2000'],
        ];
    }
}
