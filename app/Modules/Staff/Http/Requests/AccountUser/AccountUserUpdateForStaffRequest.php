<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\AccountUser;

use App\Domains\User\Models\AccountUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AccountUserUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (array_key_exists('name', $data) && $data['name'] === '') {
            $data['name'] = null;
        }

        if (isset($data['email']) && is_string($data['email'])) {
            $data['email'] = mb_strtolower(trim($data['email']));
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
            'email' => [
                'sometimes',
                'required',
                'email:rfc,dns',
                'max:255',
                Rule::unique('account_users', 'email')->ignore($this->userId()),
            ],
            'status' => ['sometimes', 'required', 'in:ACTIVE,SUSPENDED,BLOCKED'],
        ];
    }

    private function userId(): ?int
    {
        $user = $this->route('user');

        return $user instanceof AccountUser ? $user->id : null;
    }
}
