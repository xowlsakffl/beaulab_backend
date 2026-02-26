<?php

namespace App\Modules\Partner\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePasswordForPartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ];
    }

    public function attributes(): array
    {
        return [
            'current_password' => '현재 비밀번호',
            'password' => '새 비밀번호',
            'password_confirmation' => '새 비밀번호 확인',
        ];
    }

    public function filters(): array
    {
        return $this->validated();
    }
}
