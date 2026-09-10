<?php

namespace App\Modules\Staff\Http\Requests\HospitalEvent;

use App\Domains\HospitalEvent\Models\HospitalEvent;
use Illuminate\Foundation\Http\FormRequest;

final class HospitalEventPreviewContextForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('preview', HospitalEvent::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'hospital_id' => ['required', 'integer', 'min:1'],
            'doctor_ids' => ['sometimes', 'array', 'max:3'],
            'doctor_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }
}
