<?php

namespace App\Modules\Staff\Http\Requests\HospitalEventAd;

use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalEventAdListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'placement' => $this->normalizeToArray($this->input('placement')),
            'allow_status' => $this->normalizeToArray($this->input('allow_status')),
            'ad_status' => $this->normalizeToArray($this->input('ad_status')),
            'date_types' => $this->normalizeToArray($this->input('date_types') ?? $this->input('date_type')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'placement' => ['nullable', 'array'],
            'placement.*' => [Rule::in(HospitalEventAd::placements())],
            'allow_status' => ['nullable', 'array'],
            'allow_status.*' => [Rule::in(HospitalEventAd::allowStatuses())],
            'ad_status' => ['nullable', 'array'],
            'ad_status.*' => [Rule::in(HospitalEventAd::adStatuses())],
            'date_types' => ['nullable', 'array', 'min:1', 'max:2'],
            'date_types.*' => ['string', Rule::in(['created_at', 'ad_period'])],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'sort' => ['nullable', 'in:id,placement,cost,start_at,end_at,allow_status,created_at,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'q' => $validated['q'] ?? null,
            'placement' => $validated['placement'] ?? null,
            'allow_status' => $validated['allow_status'] ?? null,
            'ad_status' => $validated['ad_status'] ?? null,
            'date_types' => $validated['date_types'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'sort' => $validated['sort'] ?? null,
            'direction' => $validated['direction'] ?? null,
            'per_page' => (int) ($validated['per_page'] ?? 15),
        ];
    }

    public function attributes(): array
    {
        return [
            'q' => '검색어',
            'placement' => '광고위치',
            'placement.*' => '광고위치',
            'allow_status' => '검수상태',
            'allow_status.*' => '검수상태',
            'ad_status' => '광고상태',
            'ad_status.*' => '광고상태',
            'date_types' => '기간 기준',
            'date_types.*' => '기간 기준',
            'start_date' => '시작일',
            'end_date' => '종료일',
            'sort' => '정렬 기준',
            'direction' => '정렬 방향',
            'per_page' => '페이지당 개수',
        ];
    }

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

        $normalized = collect($value)
            ->map(static fn (mixed $item): string => trim((string) $item))
            ->filter(static fn (string $item): bool => $item !== '')
            ->unique()
            ->values()
            ->all();

        return $normalized === [] ? null : $normalized;
    }
}
