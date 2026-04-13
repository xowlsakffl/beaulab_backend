<?php

namespace App\Modules\User\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

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
