<?php

namespace App\Modules\Staff\Http\Requests\AccountStaff;

use App\Domains\AccountStaff\Support\StaffPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class StaffSaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge(['phone' => preg_replace('/\D/', '', (string) $this->phone)]);
        }
    }

    public function rules(): array
    {
        $id = $this->route('staff')?->id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'nickname' => [$id ? 'prohibited' : 'required', 'string', 'min:3', 'max:50', 'regex:/^[a-zA-Z0-9_.-]+$/', Rule::unique('account_staffs', 'nickname')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('account_staffs', 'email')->ignore($id)],
            'phone' => ['required', 'regex:/^0[0-9]{8,10}$/'],
            'department' => ['nullable', 'string', 'max:100'], 'job_title' => ['nullable', 'string', 'max:100'],
            'password' => [$id ? 'nullable' : 'required', 'string', 'max:72', Password::min(12)->letters()->numbers(), 'confirmed'],
            'role' => ['required', Rule::in(array_keys(StaffPermissionCatalog::ROLES))],
            'status' => [$id ? 'required' : 'prohibited', Rule::in(['ACTIVE', 'SUSPENDED', 'BLOCKED'])],
            'permissions' => [$id ? 'sometimes' : 'prohibited', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::in(StaffPermissionCatalog::names())],
            'updated_at' => [$id ? 'required' : 'prohibited', 'date'],
            'version' => [$id ? 'required' : 'prohibited', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => '이름', 'nickname' => '직원 ID', 'email' => '이메일', 'phone' => '전화번호', 'department' => '부서', 'job_title' => '직급', 'password' => '비밀번호', 'role' => '역할', 'status' => '계정상태', 'permissions' => '권한', 'updated_at' => '수정 시각'];
    }
}
