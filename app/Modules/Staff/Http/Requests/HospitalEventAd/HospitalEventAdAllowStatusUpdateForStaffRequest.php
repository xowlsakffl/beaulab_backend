<?php

namespace App\Modules\Staff\Http\Requests\HospitalEventAd;

use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalEventAdAllowStatusUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('ids')) {
            $this->merge(['ids' => $this->normalizeIdList($this->input('ids'))]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'distinct', Rule::exists('hospital_event_ads', 'id')->whereNull('deleted_at')],
            'allow_status' => ['required', Rule::in(HospitalEventAd::allowStatuses())],
            'reason' => [
                Rule::requiredIf(fn (): bool => $this->input('allow_status') === HospitalEventAd::ALLOW_REJECTED),
                'string',
                'max:500',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'ids' => '광고 목록',
            'ids.*' => '광고',
            'allow_status' => '검수상태',
            'reason' => '사유',
        ];
    }

    /**
     * @return array<int, int>
     */
    private function normalizeIdList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
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
            return [];
        }

        return collect($value)
            ->filter(static fn ($item): bool => is_int($item) || (is_string($item) && ctype_digit(trim($item))))
            ->map(static fn ($item): int => (int) $item)
            ->filter(static fn (int $item): bool => $item > 0)
            ->unique()
            ->values()
            ->all();
    }
}
