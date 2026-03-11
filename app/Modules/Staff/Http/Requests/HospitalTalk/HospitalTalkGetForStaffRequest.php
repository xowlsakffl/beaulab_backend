<?php

namespace App\Modules\Staff\Http\Requests\HospitalTalk;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalTalkGetForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'include' => $this->normalizeToArray($this->input('include')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'include' => ['nullable', 'array'],
            'include.*' => ['in:comments'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'include' => $validated['include'] ?? [],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'include' => '포함 항목',
            'include.*' => '포함 항목',
        ];
    }

    private function normalizeToArray(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return null;
        }

        $normalized = array_values(array_filter(array_map(
            static fn ($item) => is_string($item) ? trim($item) : null,
            $value,
        )));

        return $normalized === [] ? null : $normalized;
    }
}
