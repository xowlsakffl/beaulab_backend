<?php

namespace App\Modules\Staff\Http\Requests\AdminNote;

use App\Domains\Common\Support\AdminNote\AdminNoteTargetRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * AdminNoteListForStaffRequest 역할 정의.
 * 스태프 모듈의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class AdminNoteListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach (['target_type', 'target_id'] as $nullableKey) {
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
            'target_type' => ['required', 'string', Rule::in(AdminNoteTargetRegistry::aliases())],
            'target_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'target_type' => (string) $validated['target_type'],
            'target_id' => (int) $validated['target_id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'target_type' => '메모 대상 타입',
            'target_id' => '메모 대상 ID',
        ];
    }
}
