<?php

namespace App\Modules\User\Http\Requests\Notification;

use App\Domains\Notification\Models\NotificationDevice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class NotificationDeviceRegisterForUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach (['platform', 'device_uuid', 'push_token', 'app_version'] as $key) {
            if (array_key_exists($key, $data) && is_string($data[$key])) {
                $data[$key] = trim($data[$key]);
            }
        }

        if (isset($data['platform'])) {
            $data['platform'] = mb_strtoupper((string) $data['platform']);
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
            'platform' => [
                'required',
                Rule::in([
                    NotificationDevice::PLATFORM_IOS,
                    NotificationDevice::PLATFORM_ANDROID,
                    NotificationDevice::PLATFORM_WEB,
                ]),
            ],
            'device_uuid' => ['nullable', 'string', 'max:100'],
            'push_token' => ['required', 'string', 'max:4096'],
            'app_version' => ['nullable', 'string', 'max:32'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'platform' => '플랫폼',
            'device_uuid' => '디바이스 UUID',
            'push_token' => '푸시 토큰',
            'app_version' => '앱 버전',
            'metadata' => '디바이스 메타데이터',
        ];
    }
}
