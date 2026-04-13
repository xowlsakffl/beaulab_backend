<?php

namespace App\Modules\User\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

final class ChatOpenForUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('peer_user_id') === '') {
            $this->merge(['peer_user_id' => null]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'peer_user_id' => ['required', 'integer', 'exists:account_users,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'peer_user_id' => '상대 사용자',
        ];
    }
}
