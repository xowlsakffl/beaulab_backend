<?php

namespace App\Modules\Staff\Http\Requests\Expert;

use App\Domains\Common\Models\Category\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ExpertListForStaffRequest 역할 정의.
 * 스태프 모듈의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class ExpertListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->normalizeToArray($this->input('status')),
            'allow_status' => $this->normalizeToArray($this->input('allow_status')),
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
            'beauty_id' => ['nullable', 'integer', 'exists:beauties,id'],
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'array'],
            'status.*' => ['in:ACTIVE,SUSPENDED,INACTIVE'],
            'allow_status' => ['nullable', 'array'],
            'allow_status.*' => ['in:PENDING,APPROVED,REJECTED'],
            'category_ids' => ['nullable', 'array', 'min:1', 'max:100'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(static fn ($query) => $query
                    ->where('domain', Category::DOMAIN_BEAUTY)
                    ->where('status', Category::STATUS_ACTIVE)),
            ],
            'include' => ['nullable', 'array'],
            'include.*' => ['in:categories'],
            'sort' => ['nullable', 'in:id,name,sort_order,created_at,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'beauty_id' => $validated['beauty_id'] ?? null,
            'q' => $validated['q'] ?? null,
            'status' => $validated['status'] ?? null,
            'allow_status' => $validated['allow_status'] ?? null,
            'category_ids' => $validated['category_ids'] ?? null,
            'include' => $validated['include'] ?? [],
            'sort' => $validated['sort'] ?? 'id',
            'direction' => $validated['direction'] ?? 'desc',
            'per_page' => (int) ($validated['per_page'] ?? 15),
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

    public function attributes(): array
    {
        return [
            'beauty_id' => 'Beauty ID',
            'q' => 'Search query',
            'status' => 'Status',
            'status.*' => 'Status',
            'allow_status' => 'Allow status',
            'allow_status.*' => 'Allow status',
            'category_ids' => 'Category IDs',
            'category_ids.*' => 'Category ID',
            'include' => 'Include',
            'include.*' => 'Include',
            'sort' => 'Sort',
            'direction' => 'Direction',
            'per_page' => 'Per page',
        ];
    }
}
