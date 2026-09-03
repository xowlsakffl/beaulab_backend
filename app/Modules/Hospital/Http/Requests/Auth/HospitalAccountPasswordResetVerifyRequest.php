<?php

declare(strict_types=1);

namespace App\Modules\Hospital\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalAccountPasswordResetVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['token' => ['required', 'string', 'regex:/^[A-Za-z0-9]{64}$/D']];
    }

    public function attributes(): array
    {
        return ['token' => '비밀번호 재설정 토큰'];
    }
}
