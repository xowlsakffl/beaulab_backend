<?php

namespace App\Modules\Staff\Http\Requests\Category;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CategorySelectorListForStaffRequest 역할 정의.
 * 스태프 모듈의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class CategorySelectorListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $status = $this->normalizeToArray($this->input('status'));
        $domain = $this->input('domain');
        $usage = $this->input('usage');
        $parentCode = $this->input('parent_code');

        $this->merge([
            'status' => $status,
            'domain' => is_string($domain) ? trim($domain) : $domain,
            'usage' => is_string($usage) ? trim($usage) : $usage,
            'parent_code' => is_string($parentCode) ? trim($parentCode) : $parentCode,
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
            'usage' => ['nullable', Rule::in(CategoryUsage::usages())],
            'q' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'parent_code' => ['nullable', 'string', 'max:80'],
            'depth' => ['nullable', 'integer', 'in:1,2,3,4'],
            'status' => ['nullable', 'array'],
            'status.*' => ['in:ACTIVE,INACTIVE'],
            'is_menu_visible' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:id,name,sort_order,depth,status'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'domain' => (string) $validated['domain'],
            'usage' => $validated['usage'] ?? null,
            'q' => $validated['q'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'parent_code' => $validated['parent_code'] ?? null,
            'depth' => $validated['depth'] ?? null,
            'status' => $validated['status'] ?? null,
            'is_menu_visible' => $validated['is_menu_visible'] ?? null,
            'sort' => $validated['sort'] ?? 'sort_order',
            'direction' => $validated['direction'] ?? 'asc',
            'per_page' => (int) ($validated['per_page'] ?? 50),
        ];
    }

    public function attributes(): array
    {
        return [
            'domain' => '카테고리 분류',
            'usage' => '카테고리 사용처',
            'q' => '검색어',
            'parent_id' => '상위 카테고리 ID',
            'parent_code' => '상위 카테고리 코드',
            'depth' => '카테고리 단계',
            'status' => '카테고리 상태',
            'status.*' => '카테고리 상태',
            'is_menu_visible' => '메뉴 노출 여부',
            'sort' => '정렬 기준',
            'direction' => '정렬 방향',
            'per_page' => '조회 개수',
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
