<?php

namespace App\Modules\Staff\Http\Requests\Hashtag;

use App\Domains\Common\Hashtag\Models\Hashtag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * HashtagListForStaffRequest 역할 정의.
 * 스태프 모듈의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class HashtagListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $q = $this->input('q');
        $status = $this->input('status');

        $this->merge([
            'q' => is_string($q)
                ? trim((string) (preg_replace('/^[#＃]+/u', '', trim($q)) ?? trim($q)))
                : $q,
            'status' => $this->normalizeToArray($status),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'array'],
            'status.*' => [Rule::in(Hashtag::statuses())],
            'sort' => ['nullable', 'in:id,name,normalized_name,status,usage_count,created_at,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'q' => $validated['q'] ?? null,
            'statuses' => $validated['status'] ?? [],
            'sort' => $validated['sort'] ?? 'id',
            'direction' => $validated['direction'] ?? 'desc',
            'per_page' => (int) ($validated['per_page'] ?? 50),
        ];
    }

    public function attributes(): array
    {
        return [
            'q' => '검색어',
            'status' => '운영상태',
            'status.*' => '운영상태',
            'sort' => '정렬 기준',
            'direction' => '정렬 방향',
            'page' => '페이지',
            'per_page' => '페이지당 개수',
        ];
    }

    private function normalizeToArray(mixed $value): ?array
    {
        if ($value === null || $value === '') {
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
        } elseif (! is_array($value)) {
            $value = [$value];
        }

        return collect($value)
            ->map(static fn (mixed $item): string => strtoupper(trim((string) $item)))
            ->filter(static fn (string $item): bool => $item !== '')
            ->unique()
            ->values()
            ->all();
    }
}
