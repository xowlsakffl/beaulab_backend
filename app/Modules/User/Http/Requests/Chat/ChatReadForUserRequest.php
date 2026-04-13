<?php

namespace App\Modules\User\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ChatReadForUserRequest 역할 정의.
 * 채팅 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class ChatReadForUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('last_read_message_id') === '') {
            $this->merge(['last_read_message_id' => null]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'last_read_message_id' => ['nullable', 'integer', 'min:1', 'exists:chat_messages,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'last_read_message_id' => '마지막 읽은 메시지',
        ];
    }
}
