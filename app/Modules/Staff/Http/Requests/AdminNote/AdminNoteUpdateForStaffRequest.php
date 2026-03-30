<?php

namespace App\Modules\Staff\Http\Requests\AdminNote;

use Illuminate\Foundation\Http\FormRequest;

final class AdminNoteUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach (['note', 'is_internal'] as $nullableKey) {
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
            'note' => ['required', 'string', 'max:1000'],
            'is_internal' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'note' => '메모 내용',
            'is_internal' => '내부 메모 여부',
        ];
    }
}
