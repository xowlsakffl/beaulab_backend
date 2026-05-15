<?php

namespace App\Modules\Staff\Http\Requests\HospitalEvaluation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalEvaluationGetForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'operation_histories_page' => $this->normalizePositiveInt($this->input('operation_histories_page')),
            'operation_histories_per_page' => $this->normalizePositiveInt($this->input('operation_histories_per_page')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'operation_histories_page' => ['nullable', 'integer', 'min:1'],
            'operation_histories_per_page' => ['nullable', 'integer', Rule::in([10])],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'operation_histories_page' => (int) ($validated['operation_histories_page'] ?? 1),
            'operation_histories_per_page' => 10,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'operation_histories_page' => '운영 히스토리 페이지',
            'operation_histories_per_page' => '운영 히스토리 페이지당 개수',
        ];
    }

    private function normalizePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_string($value) && ctype_digit(trim($value))) {
            $normalized = (int) $value;

            return $normalized > 0 ? $normalized : null;
        }

        return null;
    }
}
