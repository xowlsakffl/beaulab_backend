<?php

namespace App\Modules\Staff\Http\Requests\Category;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CategoryManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'domain' => ['required', Rule::in(Category::domains())],
            'version' => [$this->isMethod('GET') ? 'sometimes' : 'required', 'string', 'size:64'],
            'operation' => [$this->isMethod('GET') ? 'sometimes' : 'required', Rule::in(['create', 'update', 'delete', 'reorder', 'usage', 'label'])],
            'id' => ['required_if:operation,update,delete,usage,label', 'integer', 'min:1'],
            'name' => ['required_if:operation,create', 'string', 'min:1', 'max:120', 'not_regex:/[>]/'],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:120', 'not_regex:/[>]/'],
            'confirm_global' => ['sometimes', 'accepted'],
            'parent_id' => ['nullable', 'integer', 'min:1'],
            'group_code' => ['nullable', Rule::in(Category::groupCodes())],
            'status' => ['sometimes', Rule::in(['ACTIVE', 'INACTIVE'])],
            'usage' => ['required_if:operation,usage,label', 'nullable', Rule::in(CategoryUsage::usages())],
            'usages' => ['sometimes', 'array', 'max:11'],
            'usages.*' => ['string', 'distinct', Rule::in(CategoryUsage::usages())],
            'enabled' => ['required_if:operation,usage', 'boolean'],
            'visible' => ['required_if:operation,usage', 'boolean'],
            'ids' => ['required_if:operation,reorder', 'array', 'min:1', 'max:2000'],
            'ids.*' => ['integer', 'min:1', 'distinct'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => '카테고리명', 'usage' => '사용처', 'domain' => '분류군'];
    }
}
