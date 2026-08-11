<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalWallet;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalWalletListForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => [
                'nullable',
                'in:hospital_id,hospital_name,total_balance,paid_balance,service_balance,active_event_count,active_ad_count,last_transaction_at',
            ],
            'direction' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'q' => $validated['q'] ?? null,
            'sort' => $validated['sort'] ?? null,
            'direction' => $validated['direction'] ?? null,
            'per_page' => (int) ($validated['per_page'] ?? 15),
        ];
    }

    public function attributes(): array
    {
        return [
            'q' => '검색어',
            'sort' => '정렬 기준',
            'direction' => '정렬 방향',
            'page' => '페이지',
            'per_page' => '페이지당 개수',
        ];
    }
}
