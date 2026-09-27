<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalChatBot;

use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationFilters;
use Illuminate\Foundation\Http\FormRequest;

final class HospitalChatBotConsultationListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['status' => HospitalChatBotConsultationFilters::splitStatus($this->input('status'))]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return HospitalChatBotConsultationFilters::rules() + [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        $validated = $this->validated();

        return HospitalChatBotConsultationFilters::fromValidated($validated)
            + ['per_page' => (int) ($validated['per_page'] ?? 20)];
    }
}
