<?php

namespace App\Modules\Staff\Http\Requests\Notice;

use Illuminate\Foundation\Http\FormRequest;

final class NoticeEditorImageUploadForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
            'notice_id' => ['nullable', 'integer', 'exists:notices,id'],
        ];
    }
}
