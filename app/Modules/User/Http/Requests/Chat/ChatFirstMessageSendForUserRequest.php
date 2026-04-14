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
            'attachments' => ['required_if:message_type,'.ChatMessage::TYPE_IMAGE, 'required_if:message_type,'.ChatMessage::TYPE_FILE, 'nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:51200'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $messageType = (string) $this->input('message_type', ChatMessage::TYPE_TEXT);
            $attachments = $this->file('attachments', []);

            if (! is_array($attachments)) {
                $attachments = $attachments ? [$attachments] : [];
            }

            if (in_array($messageType, [ChatMessage::TYPE_IMAGE, ChatMessage::TYPE_FILE], true) && $attachments === []) {
                $validator->errors()->add('attachments', '이미지/파일 메시지는 첨부파일이 필요합니다.');
            }

            if ($messageType !== ChatMessage::TYPE_IMAGE) {
                return;
            }

            foreach ($attachments as $index => $file) {
                if (! $file || ! str_starts_with((string) $file->getMimeType(), 'image/')) {
                    $validator->errors()->add("attachments.{$index}", '이미지 메시지는 이미지 파일만 첨부할 수 있습니다.');
                }
            }
        });
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
}
