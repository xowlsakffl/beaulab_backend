<?php

namespace App\Modules\Staff\Http\Requests\Faq;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Faq\Models\Faq;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class FaqListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'channel' => $this->normalizeToArray($this->input('channel')),
            'status' => $this->normalizeToArray($this->input('status')),
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
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(static fn ($query) => $query
                    ->where('domain', Category::DOMAIN_FAQ)
                    ->where('status', Category::STATUS_ACTIVE)),
            ],
            'channel' => ['nullable', 'array'],
            'channel.*' => [Rule::in(Faq::channels())],
            'status' => ['nullable', 'array'],
            'status.*' => [Rule::in(Faq::statuses())],
            'sort' => ['nullable', 'in:id,question,channel,status,sort_order,view_count,created_at,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'q' => $validated['q'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'channel' => $validated['channel'] ?? null,
            'status' => $validated['status'] ?? null,
            'sort' => $validated['sort'] ?? null,
            'direction' => $validated['direction'] ?? 'desc',
            'per_page' => (int) ($validated['per_page'] ?? 15),
        ];
    }

    public function attributes(): array
    {
        return [
            'q' => '검색어',
            'category_id' => 'FAQ 카테고리',
            'channel' => 'FAQ 채널 목록',
            'channel.*' => 'FAQ 채널',
            'status' => '운영 상태',
            'status.*' => '운영 상태',
            'sort' => '정렬 기준',
            'direction' => '정렬 방향',
            'per_page' => '페이지당 개수',
        ];
    }

    /**
     * @return array<int, string>|null
     */
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
            static fn ($item): ?string => is_scalar($item) ? trim((string) $item) : null,
            $value,
        )));

        return $normalized === [] ? null : array_values(array_unique($normalized));
    }
}
