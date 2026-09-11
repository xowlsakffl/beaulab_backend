<?php

declare(strict_types=1);

namespace App\Modules\Hospital\Http\Requests\Auth;

use App\Domains\AccountHospital\Support\AccountHospitalEmail;
use Illuminate\Foundation\Http\FormRequest;

final class SendHospitalAccountEmailVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => AccountHospitalEmail::normalize($this->input('email'))]);
        }
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
        ];
    }

    public function attributes(): array
    {
        return ['email' => '이메일'];
    }
}
