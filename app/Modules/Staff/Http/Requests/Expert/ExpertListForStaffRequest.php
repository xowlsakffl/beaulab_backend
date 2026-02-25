<?php

namespace App\Modules\Staff\Http\Requests\Expert;

use Illuminate\Foundation\Http\FormRequest;

final class ExpertListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->normalizeToArray($this->input('status')),
            'allow_status' => $this->normalizeToArray($this->input('allow_status')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'beauty_id' => ['nullable', 'integer', 'exists:beauties,id'],
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'array'],
            'status.*' => ['in:ACTIVE,SUSPENDED,INACTIVE'],
            'allow_status' => ['nullable', 'array'],
            'allow_status.*' => ['in:PENDING,APPROVED,REJECTED'],
            'sort' => ['nullable', 'in:id,name,sort_order,created_at,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'beauty_id' => $validated['beauty_id'] ?? null,
            'q' => $validated['q'] ?? null,
            'status' => $validated['status'] ?? null,
            'allow_status' => $validated['allow_status'] ?? null,
            'sort' => $validated['sort'] ?? 'id',
            'direction' => $validated['direction'] ?? 'desc',
            'per_page' => (int) ($validated['per_page'] ?? 15),
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
