<?php

namespace App\Modules\User\Http\Requests\Talk;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TalkCommentCreateForUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (array_key_exists('content', $data) && is_string($data['content'])) {
            $data['content'] = trim($data['content']);
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
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('talk_comments', 'id')->whereNull('deleted_at'),
            ],
            'content' => ['required', 'string', 'max:20000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'parent_id' => '부모 댓글',
            'content' => '댓글 내용',
        ];
    }
}
