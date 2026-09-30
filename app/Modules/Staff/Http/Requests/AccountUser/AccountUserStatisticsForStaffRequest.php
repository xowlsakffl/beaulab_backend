<?php

namespace App\Modules\Staff\Http\Requests\AccountUser;

use Illuminate\Foundation\Http\FormRequest;

final class AccountUserStatisticsForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['period' => ['nullable', 'in:daily,monthly']];
    }

    public function attributes(): array
    {
        return ['period' => '조회 단위'];
    }
}
