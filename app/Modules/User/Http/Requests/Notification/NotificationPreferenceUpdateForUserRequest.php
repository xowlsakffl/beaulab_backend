<?php

namespace App\Modules\User\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class NotificationPreferenceUpdateForUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (isset($data['event_type']) && is_string($data['event_type'])) {
            $data['event_type'] = trim($data['event_type']);
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
            'event_type' => ['required', 'string', 'max:100'],
            'in_app' => ['sometimes', 'boolean'],
            'push' => ['sometimes', 'boolean'],
            'email' => ['sometimes', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (
                    ! $this->has('in_app')
                    && ! $this->has('push')
                    && ! $this->has('email')
                    && ! $this->has('metadata')
                ) {
                    $validator->errors()->add('channel', '변경할 알림 설정 값이 필요합니다.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'event_type' => '알림 이벤트 타입',
            'in_app' => '인앱 알림 수신 여부',
            'push' => '푸시 알림 수신 여부',
            'email' => '이메일 알림 수신 여부',
            'metadata' => '알림 설정 메타데이터',
        ];
    }
}
