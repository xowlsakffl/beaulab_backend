<?php

namespace App\Modules\User\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'after_id' => ['nullable', 'integer', 'min:1'],
            'before_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('after_id') && $this->filled('before_id')) {
                $validator->errors()->add('after_id', 'after_id와 before_id는 동시에 사용할 수 없습니다.');
            }
        });
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
