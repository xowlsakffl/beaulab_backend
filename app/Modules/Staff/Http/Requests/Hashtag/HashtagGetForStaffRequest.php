<?php

namespace App\Modules\Staff\Http\Requests\Hashtag;

use Illuminate\Foundation\Http\FormRequest;

/**
 * HashtagGetForStaffRequest 역할 정의.
 * 스태프 모듈의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class HashtagGetForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
