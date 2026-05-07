<?php

namespace App\Modules\Staff\Http\Requests\TalkComment;

use App\Domains\Talk\Models\TalkComment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 토크 댓글 다중 노출 상태 변경 요청.
 */
final class TalkCommentStatusUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (array_key_exists('ids', $data)) {
            $data['ids'] = $this->normalizeIdList($data['ids']);
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
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => [
                'integer',
                'distinct',
                Rule::exists('talk_comments', 'id')->whereNull('deleted_at'),
            ],
            'status' => ['required', Rule::in(TalkComment::statuses())],
            'hidden_reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'ids' => '토크 댓글 목록',
            'ids.*' => '토크 댓글',
            'status' => '노출 상태',
            'hidden_reason' => '미노출 사유',
        ];
    }

    /**
     * @return array<int, int>
     */
    private function normalizeIdList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            $decoded = json_decode($trimmed, true);
            $value = str_starts_with($trimmed, '[')
                && json_last_error() === JSON_ERROR_NONE
                && is_array($decoded)
                && array_is_list($decoded)
                ? $decoded
                : explode(',', $trimmed);
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(static fn ($item): bool => is_int($item) || (is_string($item) && ctype_digit(trim($item))))
            ->map(static fn ($item): int => (int) $item)
            ->filter(static fn (int $item): bool => $item > 0)
            ->unique()
            ->values()
            ->all();
    }
}
