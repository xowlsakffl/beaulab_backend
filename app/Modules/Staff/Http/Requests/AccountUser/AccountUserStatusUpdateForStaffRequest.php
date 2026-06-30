<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\AccountUser;

use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AccountUserStatusUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('status'))) {
            $this->merge([
                'status' => strtoupper(trim((string) $this->input('status'))),
            ]);
        }

        if (is_string($this->input('reason'))) {
            $this->merge([
                'reason' => trim((string) $this->input('reason')),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                AccountUser::STATUS_ACTIVE,
                AccountUser::STATUS_SUSPENDED,
                AccountUser::STATUS_BLOCKED,
            ])],
            'reason' => ['nullable', 'string', 'max:500', 'required_if:status,'.AccountUser::STATUS_BLOCKED],
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => '회원 상태',
            'reason' => '사유',
        ];
    }
}
