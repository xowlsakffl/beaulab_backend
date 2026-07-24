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
        $startDate = $this->input('start_date');
        $endDate = $this->input('end_date');
        $updatedStartDate = $this->input('updated_start_date');
        $updatedEndDate = $this->input('updated_end_date');

        $this->merge([
            'q' => is_string($q)
                ? trim((string) (preg_replace('/^[#＃]+/u', '', trim($q)) ?? trim($q)))
                : $q,
            'status' => $this->normalizeToArray($status),
            'start_date' => is_string($startDate) ? trim($startDate) : $startDate,
            'end_date' => is_string($endDate) ? trim($endDate) : $endDate,
            'updated_start_date' => is_string($updatedStartDate) ? trim($updatedStartDate) : $updatedStartDate,
            'updated_end_date' => is_string($updatedEndDate) ? trim($updatedEndDate) : $updatedEndDate,
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
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'updated_start_date' => ['nullable', 'date_format:Y-m-d'],
            'updated_end_date' => ['nullable', 'date_format:Y-m-d'],
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
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'updated_start_date' => $validated['updated_start_date'] ?? null,
            'updated_end_date' => $validated['updated_end_date'] ?? null,
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
            'start_date' => '등록 시작일',
            'end_date' => '등록 종료일',
            'updated_start_date' => '수정 시작일',
            'updated_end_date' => '수정 종료일',
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
