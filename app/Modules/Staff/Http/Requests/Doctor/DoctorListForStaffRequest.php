<?php

namespace App\Modules\Staff\Http\Requests\Doctor;

use App\Domains\Common\Models\Category\Category;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * DoctorListForStaffRequest 역할 정의.
 * 스태프 모듈의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class DoctorListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->normalizeToArray($this->input('status')),
            'allow_status' => $this->normalizeToArray($this->input('allow_status')),
            'position' => $this->normalizeToArray($this->input('position')),
            'category_ids' => $this->normalizeToArray($this->input('category_ids') ?? $this->input('category_id')),
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
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'array'],
            'status.*' => ['in:ACTIVE,SUSPENDED,INACTIVE'],
            'allow_status' => ['nullable', 'array'],
            'allow_status.*' => ['in:PENDING,APPROVED,REJECTED'],
            'position' => ['nullable', 'array'],
            'position.*' => [Rule::in([
                HospitalDoctor::POSITION_HEAD_DIRECTOR,
                HospitalDoctor::POSITION_DIRECTOR,
                HospitalDoctor::POSITION_ETC,
            ])],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'updated_start_date' => ['nullable', 'date'],
            'updated_end_date' => ['nullable', 'date'],
            'category_ids' => ['nullable', 'array', 'min:1', 'max:100'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(static fn ($query) => $query
                    ->whereIn('domain', [Category::DOMAIN_HOSPITAL_TREATMENT, Category::DOMAIN_HOSPITAL_SURGERY])
                    ->where('status', Category::STATUS_ACTIVE)),
            ],
            'include' => ['nullable', 'array'],
            'include.*' => ['in:categories'],
            'is_specialist' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:id,name,gender,position,is_specialist,status,allow_status,sort_order,created_at,updated_at,view_count'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'hospital_id' => $validated['hospital_id'] ?? null,
            'q' => $validated['q'] ?? null,
            'status' => $validated['status'] ?? null,
            'allow_status' => $validated['allow_status'] ?? null,
            'position' => $validated['position'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'updated_start_date' => $validated['updated_start_date'] ?? null,
            'updated_end_date' => $validated['updated_end_date'] ?? null,
            'category_ids' => $validated['category_ids'] ?? null,
            'include' => $validated['include'] ?? [],
            'is_specialist' => array_key_exists('is_specialist', $validated)
                ? filter_var($validated['is_specialist'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                : null,
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
            'status' => '운영 상태',
            'status.*' => '운영 상태',
            'allow_status' => '검수 상태',
            'allow_status.*' => '검수 상태',
            'position' => '직책',
            'position.*' => '직책',
            'start_date' => '등록 시작일',
            'end_date' => '등록 종료일',
            'updated_start_date' => '수정 시작일',
            'updated_end_date' => '수정 종료일',
            'category_ids' => '카테고리 목록',
            'category_ids.*' => '카테고리',
            'include' => '포함 항목',
            'include.*' => '포함 항목',
            'is_specialist' => '전문의 여부',
            'sort' => '정렬 기준',
            'direction' => '정렬 방향',
            'per_page' => '페이지당 개수',
        ];
    }

}
