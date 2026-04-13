<?php

namespace App\Modules\User\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ChatListForUserRequest 역할 정의.
 * 채팅 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class ChatListForUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'include_closed' => ['nullable', 'boolean'],
        ];
    }

    public function filters(): array
    {
        return $this->validated();
    }

    public function attributes(): array
    {
        return [
            'per_page' => '페이지당 개수',
            'include_closed' => '종료 채팅 포함 여부',
        ];
    }
}
