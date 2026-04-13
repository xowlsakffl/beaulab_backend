<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\Hospital;

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

        $normalizedBusinessNumber = preg_replace('/\D+/', '', $businessNumber);

        $this->merge([
            'business_number' => $normalizedBusinessNumber !== '' ? $normalizedBusinessNumber : trim($businessNumber),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_number' => ['required', 'string', 'max:20'],
        ];
    }
}
