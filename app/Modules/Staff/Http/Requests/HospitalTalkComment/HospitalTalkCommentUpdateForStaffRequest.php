<?php

namespace App\Modules\Staff\Http\Requests\HospitalTalkComment;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalTalkCommentUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach ([
            'parent_id',
            'author_id',
            'content',
            'status',
            'is_visible',
            'admin_note',
        ] as $nullableKey) {
            if (array_key_exists($nullableKey, $data) && $data[$nullableKey] === '') {
                $data[$nullableKey] = null;
            }
        }

        if (array_key_exists('mentions', $data) && is_string($data['mentions'])) {
            $decoded = json_decode($data['mentions'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $data['mentions'] = $decoded;
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
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:hospital_talk_comments,id'],
            'author_id' => ['sometimes', 'nullable', 'integer', 'exists:account_users,id'],
            'content' => ['sometimes', 'string'],
            'status' => ['sometimes', 'nullable', 'in:ACTIVE,INACTIVE'],
            'is_visible' => ['sometimes', 'nullable', 'boolean'],
            'admin_note' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'mentions' => ['sometimes', 'nullable', 'array'],
            'mentions.user_id' => ['required_with:mentions', 'integer', 'exists:account_users,id'],
            'mentions.mention_text' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'parent_id' => '부모 댓글',
            'author_id' => '작성자',
            'content' => '내용',
            'status' => '상태',
            'is_visible' => '노출 여부',
            'admin_note' => '관리자 메모',
            'mentions' => '멘션',
            'mentions.user_id' => '멘션 사용자',
            'mentions.mention_text' => '멘션 텍스트',
        ];
    }
}
