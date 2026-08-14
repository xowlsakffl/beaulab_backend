<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalWallet;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalWalletRefundDocumentsUpdateForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_registration_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'bankbook_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'remove_business_registration_file' => ['nullable', 'boolean'],
            'remove_bankbook_file' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'business_registration_file' => '사업자등록증',
            'bankbook_file' => '통장 사본',
            'remove_business_registration_file' => '사업자등록증 삭제 여부',
            'remove_bankbook_file' => '통장 사본 삭제 여부',
        ];
    }
}
