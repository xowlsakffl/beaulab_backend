<?php

namespace App\Modules\User\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

final class NotificationDeviceRevokeForUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (isset($data['push_token']) && is_string($data['push_token'])) {
            $data['push_token'] = trim($data['push_token']);
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
            'push_token' => ['required', 'string', 'max:4096'],
        ];
    }

    public function attributes(): array
    {
        return [
            'push_token' => '푸시 토큰',
        ];
    }
}
