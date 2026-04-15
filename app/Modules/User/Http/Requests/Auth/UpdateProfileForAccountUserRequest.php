<?php

namespace App\Modules\User\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateProfileForAccountUserRequest 역할 정의.
 * 앱 사용자 모듈의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class UpdateProfileForAccountUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (array_key_exists('email', $data) && is_string($data['email'])) {
            $data['email'] = mb_strtolower(trim($data['email']));
        }

        if (array_key_exists('name', $data) && is_string($data['name'])) {
            $data['name'] = trim($data['name']);
        }

        if (array_key_exists('nickname', $data) && is_string($data['nickname'])) {
            $data['nickname'] = trim($data['nickname']);
        }

        $this->replace($data);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'nickname' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('account_users', 'nickname')->ignore($this->user()?->getAuthIdentifier()),
            ],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('account_users', 'email')->ignore($this->user()?->getAuthIdentifier()),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '실명',
            'nickname' => '닉네임',
            'email' => '이메일',
        ];
    }

    public function filters(): array
    {
        return $this->validated();
    }
}
