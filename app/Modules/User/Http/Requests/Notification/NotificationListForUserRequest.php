<?php

namespace App\Modules\User\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

/**
 * NotificationListForUserRequest 역할 정의.
 * 알림 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class NotificationListForUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'unread_only' => ['nullable', 'boolean'],
            'event_type' => ['nullable', 'string', 'max:100'],
            'target_type' => ['nullable', 'string', 'max:100'],
            'target_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function filters(): array
    {
        return $this->validated();
    }

    public function attributes(): array
    {
        return [
            'per_page' => '페이지당 개수',
            'unread_only' => '안읽은 알림만 조회 여부',
            'event_type' => '알림 이벤트 타입',
            'target_type' => '알림 대상 유형',
            'target_id' => '알림 대상 ID',
        ];
    }
}
