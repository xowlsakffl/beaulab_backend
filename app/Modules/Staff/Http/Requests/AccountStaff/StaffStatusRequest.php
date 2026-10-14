<?php

namespace App\Modules\Staff\Http\Requests\AccountStaff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StaffStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['ACTIVE', 'SUSPENDED', 'BLOCKED'])],
            'updated_at' => ['required', 'date'],
            'version' => ['required', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return ['status' => '계정상태', 'updated_at' => '수정 시각', 'version' => '수정 버전'];
    }
}
