<?php

namespace App\Modules\Staff\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * LoginForStaffRequest 역할 정의.
 * 스태프 모듈의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class LoginForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nickname' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'keep_logged_in' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nickname' => '아이디',
            'password' => '비밀번호',
            'device_name' => '디바이스명',
            'keep_logged_in' => '로그인 유지',
        ];
    }

    public function filters(): array
    {
        $data = $this->validated();

        $data['nickname'] = trim((string) $data['nickname']);
        $data['password'] = (string) $data['password'];

        if (isset($data['device_name'])) {
            $data['device_name'] = trim((string) $data['device_name']);
            if ($data['device_name'] === '') {
                $data['device_name'] = null;
            }
        }

        $data['keep_logged_in'] = (bool) ($data['keep_logged_in'] ?? false);

        return $data;
    }
}
