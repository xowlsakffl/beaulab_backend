<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalEvent;

use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class HospitalEventRealModelDBListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'genders' => $this->normalizeToArray($this->input('genders') ?? $this->input('gender')),
            'statuses' => $this->normalizeToArray($this->input('statuses') ?? $this->input('status')),
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
            'account_user_id' => ['nullable', 'integer', 'min:1'],
            'hospital_id' => ['nullable', 'integer', Rule::exists('hospitals', 'id')->whereNull('deleted_at')],
            'hospital_event_id' => ['nullable', 'integer', Rule::exists('hospital_events', 'id')->whereNull('deleted_at')],
            'genders' => ['nullable', 'array'],
            'genders.*' => ['string', Rule::in(HospitalEventRealModelDB::genders())],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => ['string', Rule::in(HospitalEventRealModelDB::statuses())],
            'birth_year_min' => ['nullable', 'integer', 'digits:4', 'min:1900', 'max:'.date('Y')],
            'birth_year_max' => ['nullable', 'integer', 'digits:4', 'min:1900', 'max:'.date('Y')],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'sort' => ['nullable', 'in:id,status,gender,birth_date,created_at,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'q' => $validated['q'] ?? null,
            'account_user_id' => $validated['account_user_id'] ?? null,
            'hospital_id' => $validated['hospital_id'] ?? null,
            'hospital_event_id' => $validated['hospital_event_id'] ?? null,
            'genders' => $validated['genders'] ?? null,
            'statuses' => $validated['statuses'] ?? null,
            'birth_year_min' => $validated['birth_year_min'] ?? null,
            'birth_year_max' => $validated['birth_year_max'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'sort' => $validated['sort'] ?? 'id',
            'direction' => $validated['direction'] ?? 'desc',
            'per_page' => (int) ($validated['per_page'] ?? 15),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $min = $this->input('birth_year_min');
            $max = $this->input('birth_year_max');

            if ($min !== null && $min !== '' && $max !== null && $max !== '' && (int) $min > (int) $max) {
                $validator->errors()->add('birth_year_min', '출생연도 최소값은 최대값보다 클 수 없습니다.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'q' => '검색어',
            'account_user_id' => '회원',
            'hospital_id' => '병의원',
            'hospital_event_id' => '이벤트',
            'genders' => '성별',
            'genders.*' => '성별',
            'statuses' => '승인상태',
            'statuses.*' => '승인상태',
            'birth_year_min' => '출생연도 최소값',
            'birth_year_max' => '출생연도 최대값',
            'start_date' => '신청 시작일',
            'end_date' => '신청 종료일',
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
        } elseif (is_int($value)) {
            $value = [(string) $value];
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
