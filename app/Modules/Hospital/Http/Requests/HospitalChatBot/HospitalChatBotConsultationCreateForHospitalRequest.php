<?php

declare(strict_types=1);

namespace App\Modules\Hospital\Http\Requests\HospitalChatBot;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalChatBotConsultationCreateForHospitalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_application' => ['nullable', 'string', 'max:100'],
            'source_page_path' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array{source_application:?string, source_page_path:?string} */
    public function context(): array
    {
        $validated = $this->validated();

        return [
            'source_application' => self::text($validated['source_application'] ?? null),
            'source_page_path' => self::text($validated['source_page_path'] ?? null),
        ];
    }

    private static function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
