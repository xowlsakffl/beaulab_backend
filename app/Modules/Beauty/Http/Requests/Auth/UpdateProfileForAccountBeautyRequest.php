<?php

namespace App\Modules\Beauty\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateProfileForAccountBeautyRequest 역할 정의.
 * 뷰티 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class UpdateProfileForAccountBeautyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '이름',
            'email' => '이메일',
        ];
    }

    public function filters(): array
    {
        return $this->validated();
    }
}
