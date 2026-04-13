<?php

namespace App\Modules\Staff\Http\Requests\Hashtag;

use App\Domains\Common\Models\Hashtag\Hashtag;
use Illuminate\Foundation\Http\FormRequest;

/**
 * HashtagCreateForStaffRequest 역할 정의.
 * 스태프 모듈의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class HashtagCreateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        $status = $this->input('status');

        $this->merge([
            'name' => is_string($name) ? Hashtag::sanitizeName($name) : $name,
            'status' => is_string($status) ? Hashtag::normalizeStatus($status) : $status,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:' . Hashtag::NAME_MAX_LENGTH, 'regex:' . Hashtag::VALID_NAME_REGEX],
            'status' => ['nullable', 'string', 'in:' . implode(',', Hashtag::STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => '해시태그명은 영문, 숫자, 한글, 밑줄(_)만 사용할 수 있습니다.',
            'name.max' => '해시태그명은 ' . Hashtag::NAME_MAX_LENGTH . '자 이하여야 합니다.',
            'status.in' => '운영상태가 올바르지 않습니다.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '해시태그명',
            'status' => '운영상태',
        ];
    }
}
