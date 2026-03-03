<?php

namespace App\Modules\Hospital\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProfileForHospitalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '이름',
            'email' => '이메일',
        ];
    }

    public function filters(): array
    {
        return $this->validated();
    }
}
