<?php

namespace App\Modules\User\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdatePasswordForAccountUserRequest 역할 정의.
 * 앱 사용자 모듈의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class UpdatePasswordForAccountUserRequest extends FormRequest
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
