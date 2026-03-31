<?php

namespace App\Modules\Staff\Http\Requests\HospitalTalk;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalTalkListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->normalizeToArray($this->input('status')),
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
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'array'],
            'status.*' => ['in:ACTIVE,INACTIVE'],
            'is_visible' => ['nullable', 'boolean'],
            'author_id' => ['nullable', 'integer', 'exists:account_users,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'include' => ['nullable', 'array'],
            'include.*' => ['in:author,categories'],
            'sort' => ['nullable', 'in:id,title,status,is_visible,is_pinned,view_count,comment_count,like_count,created_at,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'q' => $validated['q'] ?? null,
            'status' => $validated['status'] ?? null,
            'is_visible' => $validated['is_visible'] ?? null,
            'author_id' => $validated['author_id'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'include' => $validated['include'] ?? [],
            'sort' => $validated['sort'] ?? 'id',
            'direction' => $validated['direction'] ?? 'desc',
            'per_page' => (int) ($validated['per_page'] ?? 15),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'q' => '검색어',
            'status' => '운영 상태',
            'status.*' => '운영 상태',
            'is_visible' => '노출 여부',
            'author_id' => '작성자',
            'category_id' => '카테고리',
            'start_date' => '등록 시작일',
            'end_date' => '등록 종료일',
            'include' => '포함 항목',
            'include.*' => '포함 항목',
            'sort' => '정렬 기준',
            'direction' => '정렬 방향',
            'per_page' => '페이지당 개수',
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
