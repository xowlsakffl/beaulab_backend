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
            'name' => '이름',
            'email' => '이메일',
        ];
    }

    public function filters(): array
    {
        return $this->validated();
    }
}
