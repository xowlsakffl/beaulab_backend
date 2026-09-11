<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\AccountHospital;

use App\Domains\AccountHospital\Support\AccountHospitalEmail;
use Illuminate\Foundation\Http\FormRequest;

final class HospitalAccountPasswordResetSendForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('recipient_email'))) {
            $this->merge(['recipient_email' => AccountHospitalEmail::normalize($this->input('recipient_email'))]);
        }
    }

    public function rules(): array
    {
        return ['recipient_email' => ['sometimes', 'required', 'string', 'email:rfc', 'max:254']];
    }

    public function attributes(): array
    {
        return ['recipient_email' => '수신 이메일'];
    }
}
