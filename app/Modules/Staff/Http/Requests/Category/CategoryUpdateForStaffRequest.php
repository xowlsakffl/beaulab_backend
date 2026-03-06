<?php

namespace App\Modules\Staff\Http\Requests\Category;

use App\Domains\Common\Models\Category\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CategoryUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        $code = $this->input('code');

        $this->merge([
            'name' => is_string($name) ? trim($name) : $name,
            'code' => is_string($code) ? trim($code) : $code,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = $this->route('category');
        $categoryId = $category instanceof Category ? (int) $category->id : null;
        $domain = $category instanceof Category ? (string) $category->domain : null;

        return [
            'domain' => ['prohibited'],
            'parent_id' => ['prohibited'],
            'name' => ['sometimes', 'string', 'max:120'],
            'code' => [
                'sometimes',
                'nullable',
                'string',
                'max:80',
                Rule::unique('categories', 'code')
                    ->where(fn ($query) => $query->where('domain', $domain))
                    ->ignore($categoryId),
            ],
            'icon' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:ACTIVE,INACTIVE'],
            'is_menu_visible' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'domain' => '카테고리 분류',
            'parent_id' => '상위 카테고리 ID',
            'name' => '카테고리명',
            'code' => '카테고리 코드',
            'icon' => '카테고리 아이콘',
            'sort_order' => '정렬 순서',
            'status' => '카테고리 상태',
            'is_menu_visible' => '메뉴 노출 여부',
        ];
    }
}
