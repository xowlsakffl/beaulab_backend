<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalWallet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalWalletRefundCreateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $idempotencyKey = $this->input('idempotency_key') ?? $this->header('Idempotency-Key');

        $this->merge([
            'idempotency_key' => is_string($idempotencyKey) ? trim($idempotencyKey) : $idempotencyKey,
            'reason' => is_string($this->input('reason')) ? trim((string) $this->input('reason')) : $this->input('reason'),
            'bank_name' => is_string($this->input('bank_name')) ? trim((string) $this->input('bank_name')) : $this->input('bank_name'),
            'account_number' => is_string($this->input('account_number'))
                ? preg_replace('/\D+/', '', (string) $this->input('account_number'))
                : $this->input('account_number'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hospital_id' => [
                'required',
                'integer',
                Rule::exists('hospitals', 'id')->whereNull('deleted_at'),
            ],
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:500'],
            'bank_name' => ['required', 'string', 'max:50'],
            'account_number' => ['required', 'string', 'regex:/^\d{6,30}$/'],
            'business_registration_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'bankbook_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function attributes(): array
    {
        return [
            'hospital_id' => '병의원',
            'amount' => '환불 포인트',
            'reason' => '환불 사유',
            'bank_name' => '은행',
            'account_number' => '계좌번호',
            'business_registration_file' => '사업자등록증',
            'bankbook_file' => '통장 사본',
            'idempotency_key' => '중복 처리 방지 키',
        ];
    }
}
