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
            'code' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:ACTIVE,INACTIVE'],
            'is_menu_visible' => ['nullable', 'boolean'],
        ];
    }
}
