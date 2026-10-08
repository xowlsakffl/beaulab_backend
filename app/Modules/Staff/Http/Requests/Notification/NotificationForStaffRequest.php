<?php

namespace App\Modules\Staff\Http\Requests\Notification;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Notification\Support\StaffNotificationTypeRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class NotificationForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof AccountStaff && app(StaffNotificationTypeRegistry::class)->canAccess($this->user());
    }

    public function rules(): array
    {
        $types = array_keys(app(StaffNotificationTypeRegistry::class)->definitions());
        if ($this->isMethod('GET')) {
            return [
                'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
                'cursor' => ['nullable', 'string', 'max:8192'],
                'unread_only' => ['sometimes', 'boolean'],
                'event_type' => ['nullable', 'string', Rule::in($types)],
                'start_date' => ['nullable', 'date_format:Y-m-d'],
                'end_date' => ['nullable', 'date_format:Y-m-d', ...($this->filled('start_date') ? ['after_or_equal:start_date'] : [])],
            ];
        }
        if ($this->isMethod('PATCH')) {
            return ['expected_event_count' => ['required', 'integer', 'min:1'], 'latest_message_id' => ['nullable', 'integer', 'min:1']];
        }
        if ($this->isMethod('POST')) {
            return ['read_all_token' => ['required', 'string', 'max:8192']];
        }

        return [
            'preferences' => ['required', 'array', 'min:1', 'max:50'],
            'preferences.*' => ['required', 'array:event_type,in_app'],
            'preferences.*.event_type' => ['required', 'string', 'distinct', Rule::in($types)],
            'preferences.*.in_app' => ['required', 'boolean'],
        ];
    }
}
