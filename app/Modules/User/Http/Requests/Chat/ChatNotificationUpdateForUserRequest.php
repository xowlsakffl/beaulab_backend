<?php

namespace App\Modules\User\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

final class ChatNotificationUpdateForUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notifications_enabled' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'notifications_enabled' => '채팅방 알림 수신 여부',
        ];
    }
}
