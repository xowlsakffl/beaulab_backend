<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\Hospital;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalCheckNameForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $name = $this->input('name');

        if (is_string($name)) {
            $this->merge([
                'name' => trim($name),
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
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
