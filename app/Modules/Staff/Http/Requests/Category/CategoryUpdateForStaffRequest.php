<?php

namespace App\Modules\Staff\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'domain' => ['prohibited'],
            'parent_id' => ['prohibited'],
            'name' => ['sometimes', 'string', 'max:120'],
            'code' => ['sometimes', 'nullable', 'string', 'max:80'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:ACTIVE,INACTIVE'],
            'is_menu_visible' => ['sometimes', 'boolean'],
        ];
    }
}

