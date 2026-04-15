<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\AccountUser;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * AccountUserUpdateForStaffRequest 역할 정의.
 * 일반 회원 계정 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class AccountUserUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (array_key_exists('name', $data) && $data['name'] === '') {
            $data['name'] = null;
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
            'name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'nickname' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('account_users', 'nickname')->ignore($this->route('user')?->getKey()),
            ],
            'status' => ['sometimes', 'required', 'in:ACTIVE,SUSPENDED,BLOCKED'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '실명',
            'nickname' => '닉네임',
            'status' => '운영 상태',
        ];
    }
}
