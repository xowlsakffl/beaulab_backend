<?php

declare(strict_types=1);

namespace App\Modules\Hospital\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class VerifyHospitalAccountPhoneVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'digits:6'],
        ];
    }

    public function attributes(): array
    {
        return ['code' => '인증번호'];
    }
}
