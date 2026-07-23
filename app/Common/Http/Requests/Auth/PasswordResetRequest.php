<?php

namespace App\Common\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class PasswordResetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => '이메일',
            'token' => '비밀번호 재설정 토큰',
            'password' => '새 비밀번호',
            'password_confirmation' => '새 비밀번호 확인',
        ];
    }

    /**
     * @return array{email:string,token:string,password:string}
     */
    public function filters(): array
    {
        $data = $this->validated();

        return [
            'email' => mb_strtolower(trim((string) $data['email'])),
            'token' => trim((string) $data['token']),
            'password' => (string) $data['password'],
        ];
    }
}
