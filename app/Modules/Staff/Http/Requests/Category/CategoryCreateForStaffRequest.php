<?php

namespace App\Modules\Staff\Http\Requests\Category;

use App\Domains\Common\Models\Category\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CategoryCreateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        $code = $this->input('code');
        $domain = $this->input('domain');

        $this->merge([
            'name' => is_string($name) ? trim($name) : $name,
            'code' => is_string($code) ? trim($code) : $code,
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
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'code' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('categories', 'code')
                    ->where(fn ($query) => $query->where('domain', (string) $this->input('domain'))),
            ],
            'icon' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:ACTIVE,INACTIVE'],
            'is_menu_visible' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'domain' => '카테고리 분류',
            'name' => '카테고리명',
            'parent_id' => '상위 카테고리 ID',
            'code' => '카테고리 코드',
            'icon' => '카테고리 아이콘',
            'sort_order' => '정렬 순서',
            'status' => '카테고리 상태',
            'is_menu_visible' => '메뉴 노출 여부',
        ];
    }
}
