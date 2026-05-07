<?php

namespace App\Modules\Staff\Http\Requests\Faq;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FaqEditorImageCleanupForStaffRequest 역할 정의.
 * FAQ 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class FaqEditorImageCleanupForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $paths = $this->normalizeToArray($this->input('paths'));
        $urls = $this->normalizeToArray($this->input('urls'));

        $this->merge([
            'paths' => $paths,
            'urls' => $urls,
        ]);
    }

    private function normalizeToArray(mixed $value): ?array
    {
        if ($value === null) {
            return null;
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
            return null;
        }

        return array_values(array_filter(
            array_map(static fn ($item) => is_string($item) ? trim($item) : $item, $value),
            static fn ($item): bool => $item !== null && $item !== ''
        ));
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paths' => ['nullable', 'array', 'max:100'],
            'paths.*' => ['string', 'max:1000'],
            'urls' => ['nullable', 'array', 'max:100'],
            'urls.*' => ['string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'paths' => '임시 이미지 경로 목록',
            'paths.*' => '임시 이미지 경로',
            'urls' => '에디터 이미지 URL 목록',
            'urls.*' => '에디터 이미지 URL',
        ];
    }
}
