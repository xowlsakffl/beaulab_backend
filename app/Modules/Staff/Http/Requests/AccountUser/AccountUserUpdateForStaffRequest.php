<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\AccountUser;

use Illuminate\Foundation\Http\FormRequest;


final class AccountUserUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (array_key_exists('name', $data) && $data['name'] === '') {
            $data['name'] = null;
        }

        $this->replace($data);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'required', 'in:ACTIVE,SUSPENDED,BLOCKED'],
        ];
    }
}
