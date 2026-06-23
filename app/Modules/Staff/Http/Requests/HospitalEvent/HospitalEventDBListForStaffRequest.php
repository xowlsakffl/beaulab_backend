<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalEvent;

use App\Domains\HospitalEvent\Models\HospitalEventDB;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalEventDBListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'contact_methods' => $this->normalizeToArray($this->input('contact_methods') ?? $this->input('contact_method')),
            'preferred_times' => $this->normalizeToArray($this->input('preferred_times') ?? $this->input('preferred_time')),
            'statuses' => $this->normalizeToArray($this->input('statuses') ?? $this->input('status')),
            'allow_statuses' => $this->normalizeToArray($this->input('allow_statuses') ?? $this->input('allow_status')),
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
            'hospital_doctor_id' => ['nullable', 'integer', Rule::exists('hospital_doctors', 'id')->whereNull('deleted_at')],
            'contact_methods' => ['nullable', 'array'],
            'contact_methods.*' => ['string', Rule::in(HospitalEventDB::contactMethods())],
            'preferred_times' => ['nullable', 'array'],
            'preferred_times.*' => ['string', Rule::in(HospitalEventDB::preferredTimes())],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => ['string', Rule::in(HospitalEventDB::statuses())],
            'allow_statuses' => ['nullable', 'array'],
            'allow_statuses.*' => ['string', Rule::in(HospitalEventDB::allowStatuses())],
            'amount_metric' => ['nullable', 'string', Rule::in(['all', 'event_price', 'consultation_price'])],
            'amount_min' => ['nullable', 'integer', 'min:0'],
            'amount_max' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'sort' => ['nullable', 'in:id,event_price,consultation_price,status,allow_status,created_at,updated_at'],
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
            'hospital_doctor_id' => $validated['hospital_doctor_id'] ?? null,
            'contact_methods' => $validated['contact_methods'] ?? null,
            'preferred_times' => $validated['preferred_times'] ?? null,
            'statuses' => $validated['statuses'] ?? null,
            'allow_statuses' => $validated['allow_statuses'] ?? null,
            'amount_metric' => $validated['amount_metric'] ?? 'all',
            'amount_min' => isset($validated['amount_min']) ? (int) $validated['amount_min'] : null,
            'amount_max' => isset($validated['amount_max']) ? (int) $validated['amount_max'] : null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'sort' => $validated['sort'] ?? 'id',
            'direction' => $validated['direction'] ?? 'desc',
            'per_page' => (int) ($validated['per_page'] ?? 15),
        ];
    }

    public function attributes(): array
    {
        return [
            'q' => '검색어',
            'account_user_id' => '회원',
            'hospital_id' => '병의원',
            'hospital_event_id' => '이벤트',
            'hospital_doctor_id' => '의료진',
            'contact_methods' => '연락수단',
            'contact_methods.*' => '연락수단',
            'preferred_times' => '선호시간',
            'preferred_times.*' => '선호시간',
            'statuses' => '상담여부',
            'statuses.*' => '상담여부',
            'allow_statuses' => '검증상태',
            'allow_statuses.*' => '검증상태',
            'amount_metric' => '금액 기준',
            'amount_min' => '금액 최소값',
            'amount_max' => '금액 최대값',
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
