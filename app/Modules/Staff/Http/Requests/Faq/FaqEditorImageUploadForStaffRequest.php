<?php

namespace App\Modules\Staff\Http\Requests\Faq;

use Illuminate\Foundation\Http\FormRequest;

final class FaqEditorImageUploadForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
            'faq_id' => ['nullable', 'integer', 'exists:faqs,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'image' => '에디터 이미지',
            'faq_id' => 'FAQ ID',
        ];
    }
}
