<?php

namespace App\Modules\Staff\Http\Requests\Talk;

use App\Domains\Common\Models\Category\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TalkListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->normalizeToArray($this->input('status')),
            'category_codes' => $this->normalizeToArray($this->input('category_codes')),
            'category_ids' => $this->normalizeToArray($this->input('category_ids') ?? $this->input('category_id')),
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
            'category_codes' => ['nullable', 'array', 'min:1', 'max:100'],
            'category_codes.*' => ['string', Rule::in([
                'TALK_PLASTIC_PETIT',
                'TALK_BEAUTY',
                'TALK_DAILY',
                'TALK_SECRET',
            ])],
            'category_ids' => ['nullable', 'array', 'min:1', 'max:100'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(static fn ($query) => $query
                    ->where('domain', Category::DOMAIN_HOSPITAL_COMMUNITY)
                    ->where('status', Category::STATUS_ACTIVE)),
            ],
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
            'category_codes' => $validated['category_codes'] ?? null,
            'category_ids' => $validated['category_ids'] ?? null,
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
            'category_codes' => '카테고리 코드 목록',
            'category_codes.*' => '카테고리 코드',
            'category_ids' => '카테고리 목록',
            'category_ids.*' => '카테고리',
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
        } elseif (is_int($value)) {
            $value = [(string) $value];
        }

        if (! is_array($value)) {
            return null;
        }

        $normalized = array_values(array_filter(array_map(
            static function ($item): ?string {
                if (is_string($item)) {
                    $item = trim($item);
                    return $item === '' ? null : $item;
                }

                if (is_int($item)) {
                    return (string) $item;
                }

                return null;
            },
            $value,
        )));

        return $normalized === [] ? null : array_values(array_unique($normalized));
    }
}
