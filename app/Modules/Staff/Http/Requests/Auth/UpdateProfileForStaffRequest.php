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
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'phone' => ['sometimes', 'required', 'string', 'regex:/^0[0-9]{8,10}$/'],
            'version' => ['required', 'integer', 'min:0'],
            'nickname' => ['prohibited'],
            'email' => ['prohibited'],
            'department' => ['prohibited'],
            'job_title' => ['prohibited'],
            'role' => ['prohibited'],
            'roles' => ['prohibited'],
            'permissions' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '이름',
            'phone' => '전화번호',
            'version' => '정보 버전',
            'nickname' => '직원 ID',
            'email' => '이메일',
            'role' => '역할',
            'roles' => '역할',
            'permissions' => '권한',
            'status' => '계정 상태',
            'department' => '부서',
            'job_title' => '직급',
        ];
    }

    public function filters(): array
    {
        return $this->validated();
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => preg_replace('/[\s()-]/', '', $this->input('phone'))]);
        }
    }
}
