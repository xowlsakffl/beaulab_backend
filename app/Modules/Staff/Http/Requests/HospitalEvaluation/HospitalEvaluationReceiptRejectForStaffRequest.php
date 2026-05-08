<?php

namespace App\Modules\Staff\Http\Requests\HospitalEvaluation;

use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalEvaluationReceiptRejectForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::in(HospitalEvaluation::receiptRejectionReasons())],
            'reason_text' => [
                'required_if:reason,'.HospitalEvaluation::RECEIPT_REJECTION_REASON_OTHER,
                'prohibited_unless:reason,'.HospitalEvaluation::RECEIPT_REJECTION_REASON_OTHER,
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reason' => '영수증 부적합 사유',
            'reason_text' => '영수증 부적합 기타 사유',
        ];
    }
}
