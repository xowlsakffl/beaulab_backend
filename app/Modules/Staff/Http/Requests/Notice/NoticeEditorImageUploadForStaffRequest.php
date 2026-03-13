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

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'image' => '에디터 이미지',
            'notice_id' => '공지사항 ID',
        ];
    }
}
