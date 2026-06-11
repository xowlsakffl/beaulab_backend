<?php

namespace App\Modules\Staff\Http\Requests\HospitalEvent;

use App\Domains\Common\Category\Models\Category;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalEventListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'event_type' => $this->normalizeToArray($this->input('event_type')),
            'status' => $this->normalizeToArray($this->input('status')),
            'allow_status' => $this->normalizeToArray($this->input('allow_status')),
            'category_ids' => $this->normalizeToArray($this->input('category_ids') ?? $this->input('category_id')),
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
            'hospital_id' => ['nullable', 'integer', Rule::exists('hospitals', 'id')->whereNull('deleted_at')],
            'event_type' => ['nullable', 'array'],
            'event_type.*' => [Rule::in(HospitalEvent::types())],
            'status' => ['nullable', 'array'],
            'status.*' => [Rule::in(HospitalEvent::statuses())],
            'allow_status' => ['nullable', 'array'],
            'allow_status.*' => [Rule::in(HospitalEvent::allowStatuses())],
            'category_ids' => ['nullable', 'array', 'min:1', 'max:100'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(static fn ($query) => $query
                    ->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)
                    ->where('status', Category::STATUS_ACTIVE)),
            ],
            'event_price_min' => ['nullable', 'integer', 'min:0'],
            'event_price_max' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'sort' => ['nullable', 'in:id,event_price,discount_rate,view_count,consultation_count,status,allow_status,created_at,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'q' => $validated['q'] ?? null,
            'hospital_id' => $validated['hospital_id'] ?? null,
            'event_type' => $validated['event_type'] ?? null,
            'status' => $validated['status'] ?? null,
            'allow_status' => $validated['allow_status'] ?? null,
            'category_ids' => $validated['category_ids'] ?? null,
            'event_price_min' => isset($validated['event_price_min']) ? (int) $validated['event_price_min'] : null,
            'event_price_max' => isset($validated['event_price_max']) ? (int) $validated['event_price_max'] : null,
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
            'hospital_id' => '병의원',
            'event_type' => '이벤트 유형',
            'event_type.*' => '이벤트 유형',
            'status' => '노출 상태',
            'status.*' => '노출 상태',
            'allow_status' => '검수 상태',
            'allow_status.*' => '검수 상태',
            'category_ids' => '카테고리 목록',
            'category_ids.*' => '카테고리',
            'event_price_min' => '이벤트 가격 최소값',
            'event_price_max' => '이벤트 가격 최대값',
            'start_date' => '등록 시작일',
            'end_date' => '등록 종료일',
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
