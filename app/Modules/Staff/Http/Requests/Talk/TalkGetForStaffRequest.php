<?php

namespace App\Modules\Staff\Http\Requests\Talk;

use Illuminate\Foundation\Http\FormRequest;

/**
 * TalkGetForStaffRequest 역할 정의.
 * 토크 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class TalkGetForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function filters(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [];
    }
}
