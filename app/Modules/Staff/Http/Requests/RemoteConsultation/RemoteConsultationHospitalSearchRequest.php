<?php

namespace App\Modules\Staff\Http\Requests\RemoteConsultation;

use Illuminate\Foundation\Http\FormRequest;

final class RemoteConsultationHospitalSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'q' => '검색어',
            'page' => '페이지',
        ];
    }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1', 'max:100000']];
    }
}
