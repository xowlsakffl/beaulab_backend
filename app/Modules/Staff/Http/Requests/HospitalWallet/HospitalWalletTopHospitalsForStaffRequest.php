<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalWallet;

use App\Domains\HospitalWallet\Models\HospitalWalletTransactionEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalWalletTopHospitalsForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'balance_type' => [
                'nullable',
                Rule::in(HospitalWalletTransactionEntry::dashboardBalanceTypes()),
            ],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'balance_type' => $validated['balance_type'] ?? HospitalWalletTransactionEntry::BALANCE_TYPE_ALL,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
        ];
    }

    public function attributes(): array
    {
        return [
            'balance_type' => '충전금 유형',
            'start_date' => '조회 시작일',
            'end_date' => '조회 종료일',
        ];
    }
}
