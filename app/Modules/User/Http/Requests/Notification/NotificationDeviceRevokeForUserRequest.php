<?php

namespace App\Modules\User\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

/**
 * NotificationDeviceRevokeForUserRequest 역할 정의.
 * 알림 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
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
