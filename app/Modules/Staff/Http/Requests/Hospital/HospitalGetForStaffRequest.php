<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\Hospital;

use Illuminate\Foundation\Http\FormRequest;

/**
 * HospitalGetForStaffRequest 역할 정의.
 * 병원 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class HospitalGetForStaffRequest extends FormRequest
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
            'include.*' => ['in:business_registration,account_hospital,doctors,categories,features,wallet'],
            'operation_histories_page' => ['nullable', 'integer', 'min:1'],
            'operation_histories_per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'include' => $validated['include'] ?? [],
            'operation_histories_page' => (int) ($validated['operation_histories_page'] ?? 1),
            'operation_histories_per_page' => (int) ($validated['operation_histories_per_page'] ?? 10),
        ];
    }

    /**
     * @return array<int, string>|null
     */
    private function normalizeToArray(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            $decoded = json_decode($trimmed, true);
            $value = str_starts_with($trimmed, '[')
                && json_last_error() === JSON_ERROR_NONE
                && is_array($decoded)
                && array_is_list($decoded)
                ? $decoded
                : explode(',', $trimmed);
        }

        if (! is_array($value)) {
            return null;
        }

        $normalized = array_values(array_filter(array_map(
            static fn ($item) => is_string($item)
                ? (trim($item) === 'account_hospitals' ? 'account_hospital' : trim($item))
                : null,
            $value,
        )));

        return $normalized === [] ? null : array_values(array_unique($normalized));
    }

    public function attributes(): array
    {
        return [
            'include' => '포함 항목',
            'include.*' => '포함 항목',
            'operation_histories_page' => '운영 히스토리 페이지',
            'operation_histories_per_page' => '운영 히스토리 페이지당 개수',
        ];
    }
}
