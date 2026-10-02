<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalChatBot;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalChatBotConsultationReadForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['sequence' => ['required', 'integer', 'min:0']];
    }
}
