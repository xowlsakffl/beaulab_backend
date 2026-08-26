<?php

declare(strict_types=1);

namespace App\Modules\Hospital\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class SendHospitalAccountPhoneVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:30', 'regex:/^01[016789]-?\d{3,4}-?\d{4}$/'],
        ];
    }

    public function attributes(): array
    {
        return ['phone' => '휴대폰 번호'];
    }
}
