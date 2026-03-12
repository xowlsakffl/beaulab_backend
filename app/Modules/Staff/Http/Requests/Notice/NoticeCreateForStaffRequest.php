<?php

namespace App\Modules\Staff\Http\Requests\Notice;

use App\Domains\Notice\Models\Notice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class NoticeCreateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach ([
            'publish_start_at',
            'publish_end_at',
            'is_publish_period_unlimited',
            'is_push_enabled',
            'is_visible',
            'is_pinned',
            'pinned_order',
            'is_important',
            'popup_image',
        ] as $nullableKey) {
            if (array_key_exists($nullableKey, $data) && $data[$nullableKey] === '') {
                $data[$nullableKey] = null;
            }
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
            'channel' => ['required', Rule::in(Notice::channels())],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'is_visible' => ['nullable', 'boolean'],
            'is_pinned' => ['nullable', 'boolean'],
            'pinned_order' => ['nullable', 'integer', 'min:0'],
            'is_publish_period_unlimited' => ['nullable', 'boolean'],
            'publish_start_at' => ['nullable', 'date'],
            'publish_end_at' => ['nullable', 'date', 'after_or_equal:publish_start_at'],
            'is_push_enabled' => ['nullable', 'boolean'],
            'is_important' => ['nullable', 'boolean'],
            'popup_image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:20480'],
        ];
    }
}
