<?php

namespace App\Modules\Staff\Http\Requests\Notice;

use Illuminate\Foundation\Http\FormRequest;

final class NoticeEditorImageCleanupForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $paths = $this->input('paths');
        $urls = $this->input('urls');

        if (is_string($paths)) {
            $paths = array_values(array_filter(array_map('trim', explode(',', $paths))));
        }

        if (is_string($urls)) {
            $urls = array_values(array_filter(array_map('trim', explode(',', $urls))));
        }

        $this->merge([
            'paths' => is_array($paths) ? $paths : null,
            'urls' => is_array($urls) ? $urls : null,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paths' => ['nullable', 'array', 'max:100'],
            'paths.*' => ['string', 'max:1000'],
            'urls' => ['nullable', 'array', 'max:100'],
            'urls.*' => ['string', 'max:2000'],
        ];
    }
}
