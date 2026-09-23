<?php

declare(strict_types=1);

namespace App\Modules\Hospital\Http\Requests\HospitalChatBot;

use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultationMessage;
use Illuminate\Foundation\Http\FormRequest;

final class HospitalChatBotConsultationMessageSendForHospitalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:'.HospitalChatBotConsultationMessage::MAX_LENGTH],
            'client_message_id' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.max' => '메시지는 :max자 이하로 입력해 주세요.',
        ];
    }

    public function content(): string
    {
        return trim((string) $this->validated()['content']);
    }

    public function clientMessageId(): ?string
    {
        $value = $this->validated()['client_message_id'] ?? null;
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
