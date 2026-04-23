<?php

namespace App\Modules\User\Http\Requests\Talk;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * TalkPollVoteForUserRequest 역할 정의.
 * 앱 사용자의 토크 투표 요청 값을 검증한다.
 */
final class TalkPollVoteForUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'option_ids' => ['required', 'array', 'min:1', 'max:10'],
            'option_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('talk_poll_options', 'id'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'option_ids' => '투표 항목 목록',
            'option_ids.*' => '투표 항목',
        ];
    }
}
