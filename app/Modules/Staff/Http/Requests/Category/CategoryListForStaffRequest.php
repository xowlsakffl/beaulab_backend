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
        $domain = $this->input('domain');

        $this->merge([
            'status' => $status,
            'domain' => is_string($domain) ? trim($domain) : $domain,
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
            'status' => ['nullable', 'array'],
            'status.*' => ['in:ACTIVE,INACTIVE'],
            'is_menu_visible' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:id,name,sort_order,depth,status,created_at,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
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
            'status' => $validated['status'] ?? null,
            'is_menu_visible' => $validated['is_menu_visible'] ?? null,
            'sort' => $validated['sort'] ?? 'sort_order',
            'direction' => $validated['direction'] ?? 'asc',
            'per_page' => (int) ($validated['per_page'] ?? 50),
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
