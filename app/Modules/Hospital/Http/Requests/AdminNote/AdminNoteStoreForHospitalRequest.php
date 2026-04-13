<?php

namespace App\Modules\Hospital\Http\Requests\AdminNote;

use App\Domains\Common\Support\AdminNote\AdminNoteTargetRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * AdminNoteStoreForHospitalRequest 역할 정의.
 * 병원 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class AdminNoteStoreForHospitalRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    private const TARGET_TYPES = [
        AdminNoteTargetRegistry::ALIAS_HOSPITAL,
        AdminNoteTargetRegistry::ALIAS_HOSPITAL_VIDEO,
    ];

    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach (['target_type', 'target_id', 'note', 'is_internal'] as $nullableKey) {
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
            'target_type' => ['required', 'string', Rule::in(self::TARGET_TYPES)],
            'target_id' => ['required', 'integer', 'min:1'],
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
            'target_type' => '메모 대상 타입',
            'target_id' => '메모 대상 ID',
            'note' => '메모 내용',
            'is_internal' => '내부 메모 여부',
        ];
    }
}
