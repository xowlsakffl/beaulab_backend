<?php

namespace App\Modules\Staff\Http\Requests\Notice;

use App\Domains\Notice\Models\Notice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class NoticeUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach ([
            'channel',
            'title',
            'content',
            'publish_start_at',
            'publish_end_at',
            'is_publish_period_unlimited',
            'is_push_enabled',
            'is_visible',
            'is_pinned',
            'pinned_order',
            'is_important',
            'popup_image',
            'remove_popup_image',
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
            'channel' => ['sometimes', Rule::in(Notice::channels())],
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'is_visible' => ['sometimes', 'nullable', 'boolean'],
            'is_pinned' => ['sometimes', 'nullable', 'boolean'],
            'pinned_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_publish_period_unlimited' => ['sometimes', 'nullable', 'boolean'],
            'publish_start_at' => ['sometimes', 'nullable', 'date'],
            'publish_end_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:publish_start_at'],
            'is_push_enabled' => ['sometimes', 'nullable', 'boolean'],
            'is_important' => ['sometimes', 'nullable', 'boolean'],
            'popup_image' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
            'remove_popup_image' => ['sometimes', 'nullable', 'boolean'],
            'attachments' => ['sometimes', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:20480'],
        ];
    }
}
