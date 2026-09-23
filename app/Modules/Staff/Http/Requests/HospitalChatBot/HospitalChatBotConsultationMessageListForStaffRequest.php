<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalChatBot;

use App\Domains\HospitalChatBot\Actions\Shared\HospitalChatBotConsultationMessageListAction;
use Illuminate\Foundation\Http\FormRequest;

final class HospitalChatBotConsultationMessageListForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'after_sequence' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.HospitalChatBotConsultationMessageListAction::MAX_LIMIT],
        ];
    }

    public function afterSequence(): ?int
    {
        $value = $this->validated()['after_sequence'] ?? null;

        return $value === null ? null : (int) $value;
    }

    public function limit(): int
    {
        return (int) ($this->validated()['limit'] ?? HospitalChatBotConsultationMessageListAction::MAX_LIMIT);
    }
}
