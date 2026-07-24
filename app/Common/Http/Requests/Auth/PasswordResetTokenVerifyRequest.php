<?php

namespace App\Common\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class PasswordResetTokenVerifyRequest extends FormRequest
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
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => '이메일',
            'token' => '비밀번호 재설정 토큰',
        ];
    }

    /**
     * @return array{email:string,token:string}
     */
    public function filters(): array
    {
        $data = $this->validated();

        return [
            'email' => mb_strtolower(trim((string) $data['email'])),
            'token' => trim((string) $data['token']),
        ];
    }
}
