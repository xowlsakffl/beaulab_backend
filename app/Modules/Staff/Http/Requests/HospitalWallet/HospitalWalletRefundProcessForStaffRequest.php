<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalWallet;

use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalWalletRefundProcessForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $idempotencyKey = $this->input('idempotency_key') ?? $this->header('Idempotency-Key');

        $this->merge([
            'idempotency_key' => is_string($idempotencyKey) ? trim($idempotencyKey) : $idempotencyKey,
            'rejection_reason' => is_string($this->input('rejection_reason'))
                ? trim((string) $this->input('rejection_reason'))
                : $this->input('rejection_reason'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    HospitalWalletOperation::STATUS_COMPLETED,
                    HospitalWalletOperation::STATUS_REJECTED,
                ]),
            ],
            'rejection_reason' => [
                'nullable',
                'string',
                'max:500',
                Rule::requiredIf(fn (): bool => $this->input('status') === HospitalWalletOperation::STATUS_REJECTED),
            ],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => '환불 상태',
            'rejection_reason' => '환불 반려 사유',
            'idempotency_key' => '중복 처리 방지 키',
        ];
    }
}
