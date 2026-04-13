<?php

namespace App\Modules\Staff\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateProfileForStaffRequest 역할 정의.
 * 스태프 모듈의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class UpdateProfileForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'department' => ['sometimes', 'nullable', 'string', 'max:255'],
            'job_title' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '이름',
            'department' => '부서',
            'job_title' => '직함/직무',
        ];
    }

    public function filters(): array
    {
        $data = $this->validated();

        // 빈 문자열 -> null (nullable 필드)
        foreach (['department', 'job_title'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }

        return $data;
    }
}
