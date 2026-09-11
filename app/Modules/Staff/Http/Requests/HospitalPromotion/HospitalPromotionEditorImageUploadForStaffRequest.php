<?php

namespace App\Modules\Staff\Http\Requests\HospitalPromotion;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalPromotionEditorImageUploadForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
            'promotion_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
