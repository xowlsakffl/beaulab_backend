<?php

namespace App\Modules\Staff\Http\Requests\RemoteConsultation;

use Illuminate\Foundation\Http\FormRequest;

final class RemoteConsultationStatusForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'status' => '승인상태',
        ];
    }

    public function rules(): array
    {
        return ['status' => ['required', 'in:REVIEWING,REJECTED']];
    }
}
