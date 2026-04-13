<?php

namespace App\Modules\User\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ChatNotificationUpdateForUserRequest 역할 정의.
 * 채팅 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
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
