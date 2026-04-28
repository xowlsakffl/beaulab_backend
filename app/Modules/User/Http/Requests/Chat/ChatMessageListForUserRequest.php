<?php

namespace App\Modules\User\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ChatMessageListForUserRequest 역할 정의.
 * 채팅 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class ChatMessageListForUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'after_id' => ['nullable', 'integer', 'min:1', 'prohibits:before_id'],
            'before_id' => ['nullable', 'integer', 'min:1', 'prohibits:after_id'],
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
            'after_id' => '이후 메시지 ID',
            'before_id' => '이전 메시지 ID',
        ];
    }
}
