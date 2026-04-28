<?php

namespace App\Modules\User\Http\Requests\Chat;

use App\Domains\Chat\Models\ChatMessage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 첫 메시지 전송 요청 검증 객체.
 * 채팅방 ID 없이 상대 사용자와 메시지를 받아 첫 전송 시점에 채팅방을 만들 수 있게 한다.
 */
final class ChatFirstMessageSendForUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach (['peer_user_id', 'body', 'client_message_id', 'message_type', 'reply_to_message_id'] as $nullableKey) {
            if (array_key_exists($nullableKey, $data) && $data[$nullableKey] === '') {
                $data[$nullableKey] = null;
            }
        }

        if (empty($data['message_type'])) {
            $data['message_type'] = ChatMessage::TYPE_TEXT;
        } else {
            $data['message_type'] = mb_strtoupper((string) $data['message_type']);
        }

        $this->replace($data);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $messageType = $this->messageType();

        return [
            'peer_user_id' => ['required', 'integer', 'exists:account_users,id'],
            'message_type' => [
                'nullable',
                Rule::in([
                    ChatMessage::TYPE_TEXT,
                    ChatMessage::TYPE_IMAGE,
                    ChatMessage::TYPE_FILE,
                ]),
            ],
            'body' => ['required_if:message_type,'.ChatMessage::TYPE_TEXT, 'nullable', 'string', 'max:10000'],
            'client_message_id' => ['nullable', 'string', 'max:64'],
            'reply_to_message_id' => ['nullable', 'integer', 'min:1', 'exists:chat_messages,id'],
            'metadata' => ['nullable', 'array'],
            'attachments' => [
                Rule::requiredIf(in_array($messageType, [ChatMessage::TYPE_IMAGE, ChatMessage::TYPE_FILE], true)),
                'nullable',
                'array',
                'max:10',
            ],
            'attachments.*' => $this->attachmentRules($messageType),
        ];
    }

    public function attributes(): array
    {
        return [
            'peer_user_id' => '상대 사용자',
            'message_type' => '메시지 유형',
            'body' => '메시지 본문',
            'client_message_id' => '클라이언트 메시지 ID',
            'reply_to_message_id' => '답장 대상 메시지',
            'metadata' => '메시지 메타데이터',
            'attachments' => '첨부파일',
            'attachments.*' => '첨부파일',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function attachmentRules(string $messageType): array
    {
        $rules = ['file', 'max:51200'];

        if ($messageType === ChatMessage::TYPE_IMAGE) {
            $rules[] = 'image';
        }

        return $rules;
    }

    private function messageType(): string
    {
        return (string) $this->input('message_type', ChatMessage::TYPE_TEXT);
    }
}
