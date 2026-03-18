<?php

namespace App\Modules\Staff\Http\Requests\Category;

use App\Domains\Common\Models\Category\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CategoryListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $status = $this->normalizeToArray($this->input('status'));
        $include = $this->normalizeToArray($this->input('include'));
        $domain = $this->input('domain');
        $withoutPagination = $this->has('without_pagination')
            ? $this->boolean('without_pagination')
            : null;

        $this->merge([
            'status' => $status,
            'include' => $include,
            'domain' => is_string($domain) ? trim($domain) : $domain,
            'without_pagination' => $withoutPagination,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'domain' => ['required', Rule::in(Category::domains())],
            'q' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'depth' => ['nullable', 'integer', 'in:1,2,3'],
            'status' => ['nullable', 'array'],
            'status.*' => ['in:ACTIVE,INACTIVE'],
            'include' => ['nullable', 'array'],
            'include.*' => ['in:parent,children'],
            'is_menu_visible' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:id,name,sort_order,depth,status,created_at,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'without_pagination' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'domain' => (string) $validated['domain'],
            'q' => $validated['q'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'depth' => $validated['depth'] ?? null,
            'status' => $validated['status'] ?? null,
            'include' => $validated['include'] ?? [],
            'is_menu_visible' => $validated['is_menu_visible'] ?? null,
            'sort' => $validated['sort'] ?? 'sort_order',
            'direction' => $validated['direction'] ?? 'asc',
            'without_pagination' => (bool) ($validated['without_pagination'] ?? false),
            'per_page' => (int) ($validated['per_page'] ?? 50),
        ];
    }

    public function attributes(): array
    {
        return [
            'domain' => '카테고리 분류',
            'q' => '검색어',
            'parent_id' => '상위 카테고리 ID',
            'depth' => '카테고리 단계',
            'status' => '카테고리 상태',
            'status.*' => '카테고리 상태',
            'include' => '포함 항목',
            'include.*' => '포함 항목',
            'is_menu_visible' => '메뉴 노출 여부',
            'sort' => '정렬 기준',
            'direction' => '정렬 방향',
            'without_pagination' => '비페이지네이션 여부',
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
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return null;
        }

        $normalized = array_values(array_filter(array_map(
            static fn ($item) => is_string($item) ? trim($item) : null,
            $value,
        )));

        return $normalized === [] ? null : array_values(array_unique($normalized));
    }
}
