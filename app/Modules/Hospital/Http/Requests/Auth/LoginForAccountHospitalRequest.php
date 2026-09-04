<?php

namespace App\Modules\Hospital\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * LoginForAccountHospitalRequest 역할 정의.
 * 병원 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class LoginForAccountHospitalRequest extends FormRequest
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
        ];
    }

    public function attributes(): array
    {
        return [
            'nickname' => '아이디',
            'password' => '비밀번호',
        ];
    }

    public function filters(): array
    {
        $data = $this->validated();

        $data['nickname'] = trim((string) $data['nickname']);
        $data['password'] = (string) $data['password'];

        return $data;
    }
}
