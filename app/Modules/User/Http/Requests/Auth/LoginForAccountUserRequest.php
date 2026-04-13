<?php

namespace App\Modules\User\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class LoginForAccountUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => '이메일',
            'password' => '비밀번호',
            'device_name' => '디바이스명',
        ];
    }

    public function filters(): array
    {
        $data = $this->validated();

        $data['email'] = mb_strtolower(trim((string) $data['email']));
        $data['password'] = (string) $data['password'];

        if (isset($data['device_name'])) {
            $data['device_name'] = trim((string) $data['device_name']);
            if ($data['device_name'] === '') {
                $data['device_name'] = null;
            }
        }

        return $data;
    }
}
