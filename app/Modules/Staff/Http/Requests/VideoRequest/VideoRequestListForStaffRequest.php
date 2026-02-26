<?php

namespace App\Modules\Staff\Http\Requests\VideoRequest;

use Illuminate\Foundation\Http\FormRequest;

final class VideoRequestListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'review_status' => $this->normalizeToArray($this->input('review_status')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
            'beauty_id' => ['nullable', 'integer', 'exists:beauties,id'],
            'q' => ['nullable', 'string', 'max:100'],
            'review_status' => ['nullable', 'array'],
            'review_status.*' => ['in:PENDING,IN_REVIEW,APPROVED,REJECTED'],
            'sort' => ['nullable', 'in:id,title,review_status,created_at,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'hospital_id' => $validated['hospital_id'] ?? null,
            'beauty_id' => $validated['beauty_id'] ?? null,
            'q' => $validated['q'] ?? null,
            'review_status' => $validated['review_status'] ?? null,
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
