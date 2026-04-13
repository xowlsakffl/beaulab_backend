<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\Hospital;

use Illuminate\Foundation\Http\FormRequest;

/**
 * HospitalCheckNameForStaffRequest 역할 정의.
 * 병원 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class HospitalCheckNameForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $name = $this->input('name');

        if (is_string($name)) {
            $this->merge([
                'name' => trim($name),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
