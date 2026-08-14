<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalWallet;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalWalletDashboardForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year' => ['nullable', 'integer', 'min:2020', 'max:'.now()->year],
        ];
    }

    public function filters(): array
    {
        return [
            'year' => (int) ($this->validated('year') ?? now()->year),
        ];
    }

    public function attributes(): array
    {
        return [
            'year' => '조회 연도',
        ];
    }
}
