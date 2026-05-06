<?php

namespace App\Modules\User\Http\Requests\Talk;

use App\Domains\AccountUser\Models\AccountUser;
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

        if (array_key_exists('parent_id', $data)) {
            $parentId = is_string($data['parent_id'])
                ? trim($data['parent_id'])
                : $data['parent_id'];

            if ($parentId === '' || (int) $parentId <= 0) {
                $data['parent_id'] = null;
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
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('talk_comments', 'id')->whereNull('deleted_at'),
            ],
            'content' => ['required', 'string', 'max:20000'],
            'mention' => ['nullable', 'array'],
            'mention.mentioned_user_id' => [
                'required_with:mention',
                'integer',
                Rule::exists('account_users', 'id')->where(static fn ($query) => $query
                    ->whereNull('deleted_at')
                    ->where('status', AccountUser::STATUS_ACTIVE)),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'parent_id' => '부모 댓글',
            'content' => '댓글 내용',
            'mention' => '멘션',
            'mention.mentioned_user_id' => '멘션 대상 사용자',
        ];
    }
}
