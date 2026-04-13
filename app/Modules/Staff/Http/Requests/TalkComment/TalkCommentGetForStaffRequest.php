<?php

namespace App\Modules\Staff\Http\Requests\TalkComment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * TalkCommentGetForStaffRequest 역할 정의.
 * 스태프 모듈의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class TalkCommentGetForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'include' => $this->normalizeToArray($this->input('include')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'include' => ['nullable', 'array'],
            'include.*' => ['in:children,mentions'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'include' => $validated['include'] ?? [],
        ];
    }

    private function normalizeToArray(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return null;
        }

        $normalized = array_values(array_filter(array_map(
            static fn ($item) => is_string($item) ? trim($item) : null,
            $value,
        )));

        return $normalized === [] ? null : $normalized;
    }
}
