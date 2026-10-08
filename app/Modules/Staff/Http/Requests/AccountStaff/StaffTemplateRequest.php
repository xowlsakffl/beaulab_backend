<?php

namespace App\Modules\Staff\Http\Requests\AccountStaff;

use App\Domains\AccountStaff\Support\StaffPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StaffTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['role' => ['required', Rule::in(array_keys(StaffPermissionCatalog::ROLES))], 'permissions' => ['present', 'array'], 'permissions.*' => ['string', 'distinct', Rule::in(StaffPermissionCatalog::names())], 'version' => ['required', 'string', 'size:64']];
    }
}
