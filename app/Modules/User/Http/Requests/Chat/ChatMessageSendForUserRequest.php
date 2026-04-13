<?php

namespace App\Modules\User\Http\Requests\Chat;

use App\Domains\Chat\Models\ChatMessage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ChatMessageSendForUserRequest 역할 정의.
 * 채팅 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class ChatMessageSendForUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach (['body', 'client_message_id', 'message_type', 'reply_to_message_id'] as $nullableKey) {
            if (array_key_exists($nullableKey, $data) && $data[$nullableKey] === '') {
                $data[$nullableKey] = null;
            }
        }

        if (empty($data['message_type'])) {
            $data['message_type'] = ChatMessage::TYPE_TEXT;
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
            'message_type' => [
                'nullable',
                Rule::in([
                    ChatMessage::TYPE_TEXT,
                ]),
            ],
            'body' => ['required', 'string', 'max:10000'],
            'client_message_id' => ['nullable', 'string', 'max:64'],
            'reply_to_message_id' => ['nullable', 'integer', 'min:1', 'exists:chat_messages,id'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'message_type' => '메시지 유형',
            'body' => '메시지 본문',
            'client_message_id' => '클라이언트 메시지 ID',
            'reply_to_message_id' => '답장 대상 메시지',
            'metadata' => '메시지 메타데이터',
        ];
    }
}
