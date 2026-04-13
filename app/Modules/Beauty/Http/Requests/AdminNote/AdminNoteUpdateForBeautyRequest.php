<?php

namespace App\Modules\Beauty\Http\Requests\AdminNote;

use Illuminate\Foundation\Http\FormRequest;

/**
 * AdminNoteUpdateForBeautyRequest 역할 정의.
 * 뷰티 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class AdminNoteUpdateForBeautyRequest extends FormRequest
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
