<?php

namespace App\Modules\Staff\Http\Requests\RemoteConsultation;

use Illuminate\Foundation\Http\FormRequest;

final class RemoteConsultationAssignForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'hospital_id' => '병의원',
            'hospital_ids' => '선택 병의원',
            'hospital_ids.*' => '선택 병의원',
        ];
    }

    public function rules(): array
    {
        return [
            'hospital_id' => ['required_without:hospital_ids', 'prohibits:hospital_ids', 'integer', 'min:1'],
            'hospital_ids' => ['required_without:hospital_id', 'prohibits:hospital_id', 'array', 'min:1', 'max:6'],
            'hospital_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }
}
