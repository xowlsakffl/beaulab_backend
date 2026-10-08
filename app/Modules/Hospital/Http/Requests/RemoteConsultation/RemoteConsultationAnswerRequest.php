<?php

namespace App\Modules\Hospital\Http\Requests\RemoteConsultation;

use Illuminate\Foundation\Http\FormRequest;

final class RemoteConsultationAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['answer' => ['required', 'string', 'max:20000'], 'total_amount' => ['required', 'integer', 'min:0', 'max:2000000000']];
    }

    public function attributes(): array
    {
        return ['answer' => '답변', 'total_amount' => '총 예상비용'];
    }
}
