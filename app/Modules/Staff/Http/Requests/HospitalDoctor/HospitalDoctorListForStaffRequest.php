<?php

namespace App\Modules\Staff\Http\Requests\HospitalDoctor;

use App\Domains\Common\Category\Models\Category;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * DoctorListForStaffRequest 역할 정의.
 * 스태프 모듈의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class HospitalDoctorListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'allow_status' => $this->normalizeToArray($this->input('allow_status')),
            'position' => $this->normalizeToArray($this->input('position')),
            'specialist_field' => $this->normalizeToArray($this->input('specialist_field')),
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
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
            'q' => ['nullable', 'string', 'max:100'],
            'allow_status' => ['nullable', 'array'],
            'allow_status.*' => ['in:PENDING,APPROVED,REJECTED'],
            'position' => ['nullable', 'array'],
            'position.*' => [Rule::in(HospitalDoctor::positions())],
            'specialist_field' => ['nullable', 'array'],
            'specialist_field.*' => [Rule::in(HospitalDoctor::specialistFields())],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'category_ids' => ['nullable', 'array', 'min:1', 'max:100'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(static fn ($query) => $query
                    ->whereIn('domain', [Category::DOMAIN_HOSPITAL_REVIEW_TREATMENT, Category::DOMAIN_HOSPITAL_REVIEW_SURGERY])
                    ->where('depth', 1)
                    ->where('status', Category::STATUS_ACTIVE)),
            ],
            'metric' => ['nullable', Rule::in(['career_years', 'review_count', 'consultation_count'])],
            'metric_min' => ['nullable', 'integer', 'min:0'],
            'metric_max' => ['nullable', 'integer', 'min:0'],
            'sort' => ['nullable', 'in:id,name,gender,position,specialist_field,allow_status,created_at,career_years,review_count,consultation_count'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $metricMin = $this->input('metric_min');
            $metricMax = $this->input('metric_max');

            if ($metricMin !== null && $metricMax !== null && (int) $metricMin > (int) $metricMax) {
                $validator->errors()->add('metric_max', '지표 최대값은 최소값보다 크거나 같아야 합니다.');
            }
        });
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'hospital_id' => $validated['hospital_id'] ?? null,
            'q' => $validated['q'] ?? null,
            'allow_status' => $validated['allow_status'] ?? null,
            'position' => $validated['position'] ?? null,
            'specialist_field' => $validated['specialist_field'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'category_ids' => $validated['category_ids'] ?? null,
            'metric' => $validated['metric'] ?? null,
            'metric_min' => $validated['metric_min'] ?? null,
            'metric_max' => $validated['metric_max'] ?? null,
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

        $normalized = array_values(array_filter(array_map(
            static function ($item): ?string {
                if (is_string($item)) {
                    $item = trim($item);
                    return $item === '' ? null : $item;
                }

                if (is_int($item)) {
                    return (string) $item;
                }

                return null;
            },
            $value,
        )));

        return $normalized === [] ? null : array_values(array_unique($normalized));
    }

    public function attributes(): array
    {
        return [
            'hospital_id' => '병원 ID',
            'q' => '검색어',
            'allow_status' => '검수 상태',
            'allow_status.*' => '검수 상태',
            'position' => '직책',
            'position.*' => '직책',
            'specialist_field' => '전문의 분류',
            'specialist_field.*' => '전문의 분류',
            'start_date' => '등록 시작일',
            'end_date' => '등록 종료일',
            'category_ids' => '진료분야',
            'category_ids.*' => '진료분야',
            'metric' => '지표',
            'metric_min' => '지표 최소값',
            'metric_max' => '지표 최대값',
            'sort' => '정렬 기준',
            'direction' => '정렬 방향',
            'per_page' => '페이지당 개수',
        ];
    }
}
