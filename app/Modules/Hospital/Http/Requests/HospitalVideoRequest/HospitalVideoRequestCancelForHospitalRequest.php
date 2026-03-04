<?php

namespace App\Modules\Hospital\Http\Requests\HospitalVideoRequest;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalVideoRequestCancelForHospitalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }
}
