<?php

namespace App\Modules\User\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProfileForAccountUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (array_key_exists('email', $data) && is_string($data['email'])) {
            $data['email'] = mb_strtolower(trim($data['email']));
        }

        if (array_key_exists('name', $data) && is_string($data['name'])) {
            $data['name'] = trim($data['name']);
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
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('account_users', 'email')->ignore($this->user()?->getAuthIdentifier()),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '이름',
            'email' => '이메일',
        ];
    }

    public function filters(): array
    {
        return $this->validated();
    }
}
