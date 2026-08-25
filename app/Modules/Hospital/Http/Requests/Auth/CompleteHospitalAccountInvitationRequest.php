<?php

declare(strict_types=1);

namespace App\Modules\Hospital\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class CompleteHospitalAccountInvitationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('nickname'))) {
            $this->merge(['nickname' => trim((string) $this->input('nickname'))]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nickname' => [
                'required',
                'string',
                'min:4',
                'max:50',
                'regex:/^[A-Za-z0-9._-]+$/',
            ],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'identity_verification_token' => ['required', 'string', 'size:64', 'alpha_num:ascii'],
        ];
    }

    public function messages(): array
    {
        return [
            'nickname.regex' => '아이디는 영문, 숫자, 마침표, 밑줄, 하이픈만 사용할 수 있습니다.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nickname' => '아이디',
            'password' => '비밀번호',
            'password_confirmation' => '비밀번호 확인',
            'identity_verification_token' => '휴대폰 본인확인',
        ];
    }
}
