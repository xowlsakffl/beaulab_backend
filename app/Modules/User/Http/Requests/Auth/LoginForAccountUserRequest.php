<?php

namespace App\Modules\User\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * LoginForAccountUserRequest 역할 정의.
 * 앱 사용자 모듈의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class LoginForAccountUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => '이메일',
            'password' => '비밀번호',
            'device_name' => '디바이스명',
        ];
    }

    public function filters(): array
    {
        $data = $this->validated();

        $data['email'] = mb_strtolower(trim((string) $data['email']));
        $data['password'] = (string) $data['password'];

        if (isset($data['device_name'])) {
            $data['device_name'] = trim((string) $data['device_name']);
            if ($data['device_name'] === '') {
                $data['device_name'] = null;
            }
        }

        return $data;
    }
}
