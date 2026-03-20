<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\Hospital;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalCheckBusinessNumberForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $businessNumber = $this->input('business_number');

        if (! is_string($businessNumber)) {
            return;
        }

        $normalizedBusinessNumber = preg_replace('/\D+/', '', $businessNumber);

        $this->merge([
            'business_number' => $normalizedBusinessNumber !== '' ? $normalizedBusinessNumber : trim($businessNumber),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_number' => ['required', 'string', 'max:20'],
        ];
    }
}
