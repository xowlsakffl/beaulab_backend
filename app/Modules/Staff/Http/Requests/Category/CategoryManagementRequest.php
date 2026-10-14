<?php

namespace App\Modules\Staff\Http\Requests\Category;

use App\Domains\Common\Category\Models\Category;
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
            'operation' => [$this->isMethod('GET') ? 'sometimes' : 'required', Rule::in(['create', 'update', 'delete', 'reorder', 'move'])],
            'id' => ['required_if:operation,update,delete,move', 'integer', 'min:1'],
            'name' => ['required_if:operation,create', 'string', 'min:1', 'max:120', 'not_regex:/[>]/'],
            'parent_id' => ['nullable', 'integer', 'min:1'],
            'group_code' => ['nullable', Rule::in(Category::groupCodes())],
            'status' => ['sometimes', Rule::in(['ACTIVE', 'INACTIVE'])],
            'is_menu_visible' => ['prohibited'],
            'topic_ids' => ['prohibited'],
            'confirm_move' => ['accepted_if:operation,move'],
            'ids' => ['required_if:operation,reorder', 'array', 'min:1', 'max:2000'],
            'ids.*' => ['integer', 'min:1', 'distinct'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => '카테고리명', 'usage' => '사용처', 'domain' => '분류군'];
    }
}
