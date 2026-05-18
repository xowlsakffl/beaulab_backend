<?php

namespace App\Modules\User\Http\Requests\Chat;

use App\Domains\Common\ContentReport\Models\ContentReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ChatMessageReportForUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (array_key_exists('message_ids', $data)) {
            $data['message_ids'] = $this->normalizeMessageIds($data['message_ids']);
        }

        if (array_key_exists('reason', $data) && is_string($data['reason'])) {
            $data['reason'] = mb_strtoupper(trim($data['reason']));
        }

        if (array_key_exists('reason_text', $data) && $data['reason_text'] === '') {
            $data['reason_text'] = null;
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
            'message_ids' => ['required', 'array', 'min:1', 'max:5'],
            'message_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('chat_messages', 'id'),
            ],
            'reason' => ['required', Rule::in(ContentReport::reasons())],
            'reason_text' => ['nullable', 'required_if:reason,'.ContentReport::REASON_OTHER, 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'message_ids' => '신고 메시지',
            'message_ids.*' => '신고 메시지',
            'reason' => '신고 사유',
            'reason_text' => '기타 신고 사유',
        ];
    }

    private function normalizeMessageIds(mixed $value): mixed
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_int($value)) {
            return [$value];
        }

        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        $decoded = json_decode($trimmed, true);

        if (
            str_starts_with($trimmed, '[')
            && json_last_error() === JSON_ERROR_NONE
            && is_array($decoded)
            && array_is_list($decoded)
        ) {
            return $decoded;
        }

        return explode(',', $trimmed);
    }
}
