<?php

namespace App\Modules\User\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ChatOpenForUserRequest 역할 정의.
 * 채팅 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class ChatOpenForUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('peer_user_id') === '') {
            $this->merge(['peer_user_id' => null]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'peer_user_id' => ['required', 'integer', 'exists:account_users,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'peer_user_id' => '상대 사용자',
        ];
    }
}
