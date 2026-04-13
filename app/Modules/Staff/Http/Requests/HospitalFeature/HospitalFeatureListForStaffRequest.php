<?php

namespace App\Modules\Staff\Http\Requests\HospitalFeature;

use App\Domains\HospitalFeature\Models\HospitalFeature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * HospitalFeatureListForStaffRequest 역할 정의.
 * 병원 특징 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class HospitalFeatureListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->normalizeToArray($this->input('status')),
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
            'status' => ['nullable', 'array'],
            'status.*' => [Rule::in([
                HospitalFeature::STATUS_ACTIVE,
                HospitalFeature::STATUS_INACTIVE,
            ])],
            'sort' => ['nullable', 'in:id,code,name,sort_order,status'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'q' => $validated['q'] ?? null,
            'status' => $validated['status'] ?? [HospitalFeature::STATUS_ACTIVE],
            'sort' => $validated['sort'] ?? 'sort_order',
            'direction' => $validated['direction'] ?? 'asc',
        ];
    }

    public function attributes(): array
    {
        return [
            'q' => '검색어',
            'status' => '운영 상태',
            'status.*' => '운영 상태',
            'sort' => '정렬 기준',
            'direction' => '정렬 방향',
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
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return null;
        }

        $normalized = array_values(array_filter(array_map(
            static fn ($item) => is_string($item) ? trim($item) : null,
            $value,
        )));

        return $normalized === [] ? null : array_values(array_unique($normalized));
    }
}
