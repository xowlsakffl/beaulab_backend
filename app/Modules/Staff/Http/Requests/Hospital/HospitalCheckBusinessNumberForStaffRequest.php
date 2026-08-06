<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\Hospital;

use App\Common\Support\BusinessNumber;
use Illuminate\Foundation\Http\FormRequest;

/**
 * HospitalCheckBusinessNumberForStaffRequest 역할 정의.
 * 병원 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class HospitalCheckBusinessNumberForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $businessNumber = $this->input('business_number');

        if (! is_string($businessNumber)) {
            return;
        }

        $this->merge([
            'business_number' => BusinessNumber::normalize($businessNumber),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_number' => ['required', 'string', BusinessNumber::VALIDATION_RULE],
        ];
    }

    public function messages(): array
    {
        return [
            'business_number.regex' => '사업자등록번호는 숫자 10자리로 입력해 주세요.',
        ];
    }
}
