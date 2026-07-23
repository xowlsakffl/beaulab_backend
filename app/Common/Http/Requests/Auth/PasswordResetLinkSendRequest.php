<?php

namespace App\Common\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class PasswordResetLinkSendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => '이메일',
        ];
    }

    /**
     * @return array{email:string}
     */
    public function filters(): array
    {
        $data = $this->validated();

        return [
            'email' => mb_strtolower(trim((string) $data['email'])),
        ];
    }
}
