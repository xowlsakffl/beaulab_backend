<?php

namespace App\Modules\Staff\Http\Requests\AccountStaff;

use App\Domains\AccountStaff\Support\StaffPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StaffListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'], 'statuses' => ['nullable', 'array'],
            'statuses.*' => [Rule::in(['ACTIVE', 'SUSPENDED', 'BLOCKED'])], 'roles' => ['nullable', 'array'],
            'roles.*' => [Rule::in(array_keys(StaffPermissionCatalog::ROLES))],
            'date_field' => ['nullable', Rule::in(['created_at', 'updated_at'])],
            'date_from' => ['nullable', 'date_format:Y-m-d'], 'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'sort' => ['nullable', Rule::in(['id', 'name', 'created_at', 'updated_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])], 'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'operation_histories_page' => ['nullable', 'integer', 'min:1'],
            'operation_histories_per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
