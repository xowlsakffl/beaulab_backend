<?php

namespace App\Modules\User\Http\Requests\HospitalReview;

use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalReviewCommentCreateForUserRequest extends FormRequest
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

        if (isset($data['mention']) && is_array($data['mention'])) {
            if (array_key_exists('mention_text', $data['mention']) && is_string($data['mention']['mention_text'])) {
                $data['mention']['mention_text'] = trim($data['mention']['mention_text']);
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
                Rule::exists('hospital_review_comments', 'id')->whereNull('deleted_at'),
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
            'mention.mention_text' => ['required_with:mention', 'string', 'max:120'],
            'mention.start_offset' => ['nullable', 'integer', 'min:0'],
            'mention.end_offset' => ['nullable', 'integer', 'gte:mention.start_offset'],
        ];
    }

    public function attributes(): array
    {
        return [
            'parent_id' => '부모 댓글',
            'content' => '댓글 내용',
            'mention' => '멘션',
            'mention.mentioned_user_id' => '멘션 대상 사용자',
            'mention.mention_text' => '멘션 텍스트',
            'mention.start_offset' => '멘션 시작 위치',
            'mention.end_offset' => '멘션 끝 위치',
        ];
    }
}
