<?php

namespace App\Modules\Staff\Http\Requests\HospitalReview;

use App\Domains\Common\Category\Models\Category;
use App\Domains\HospitalReview\Models\HospitalReview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalReviewListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->normalizeToArray($this->input('status')),
            'category_ids' => $this->normalizeToArray($this->input('category_ids') ?? $this->input('category_id')),
            'ratings' => $this->normalizeToArray($this->input('ratings') ?? $this->input('rating')),
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
            'status.*' => [Rule::in(HospitalReview::statuses())],
            'author_id' => ['nullable', 'integer', 'exists:account_users,id'],
            'hospital_id' => ['nullable', 'integer', Rule::exists('hospitals', 'id')->where(static fn ($query) => $query->whereNull('deleted_at'))],
            'doctor_id' => ['nullable', 'integer', Rule::exists('hospital_doctors', 'id')->where(static fn ($query) => $query->whereNull('deleted_at'))],
            'category_ids' => ['nullable', 'array', 'min:1', 'max:100'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(fn ($query) => $query
                    ->where('domain', $this->categoryDomain())
                    ->where('status', Category::STATUS_ACTIVE)),
            ],
            'ratings' => ['nullable', 'array', 'min:1', 'max:5'],
            'ratings.*' => ['integer', 'distinct', Rule::in([1, 2, 3, 4, 5])],
            'is_main_featured' => ['nullable', 'boolean'],
            'is_sub_featured' => ['nullable', 'boolean'],
            'metric' => ['nullable', 'required_with:metric_min,metric_max', Rule::in([
                'like_count',
                'save_count',
                'comment_count',
                'view_count',
            ])],
            'metric_min' => ['nullable', 'integer', 'min:0'],
            'metric_max' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'sort' => ['nullable', 'in:id,cost,rating,status,is_main_featured,is_sub_featured,view_count,comment_count,like_count,save_count,created_at,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'q' => $validated['q'] ?? null,
            'status' => $validated['status'] ?? null,
            'author_id' => $validated['author_id'] ?? null,
            'hospital_id' => $validated['hospital_id'] ?? null,
            'doctor_id' => $validated['doctor_id'] ?? null,
            'category_ids' => $validated['category_ids'] ?? null,
            'ratings' => $validated['ratings'] ?? null,
            'is_main_featured' => $this->has('is_main_featured') ? $this->boolean('is_main_featured') : null,
            'is_sub_featured' => $this->has('is_sub_featured') ? $this->boolean('is_sub_featured') : null,
            'metric' => $validated['metric'] ?? null,
            'metric_min' => isset($validated['metric_min']) ? (int) $validated['metric_min'] : null,
            'metric_max' => isset($validated['metric_max']) ? (int) $validated['metric_max'] : null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'sort' => $validated['sort'] ?? 'id',
            'direction' => $validated['direction'] ?? 'desc',
            'per_page' => (int) ($validated['per_page'] ?? 15),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'q' => '검색어',
            'status' => '노출 여부',
            'status.*' => '노출 여부',
            'author_id' => '작성자',
            'hospital_id' => '병의원',
            'doctor_id' => '의료진',
            'category_ids' => '카테고리 목록',
            'category_ids.*' => '카테고리',
            'ratings' => '평점 목록',
            'ratings.*' => '평점',
            'is_main_featured' => '전체 상단 노출 여부',
            'is_sub_featured' => '카테고리 상단 노출 여부',
            'metric' => '지표',
            'metric_min' => '지표 최소값',
            'metric_max' => '지표 최대값',
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

    private function categoryDomain(): string
    {
        $routeName = (string) $this->route()?->getName();

        if (str_ends_with($routeName, 'hospital-reviews.getTreatmentHospitalReviewsForStaff')) {
            return HospitalReview::CATEGORY_DOMAIN_TREATMENT;
        }

        if (str_ends_with($routeName, 'hospital-reviews.getSurgeryHospitalReviewsForStaff')) {
            return HospitalReview::CATEGORY_DOMAIN_SURGERY;
        }

        $categoryDomain = $this->route('category_domain');

        return is_string($categoryDomain) && in_array($categoryDomain, HospitalReview::categoryDomains(), true)
            ? $categoryDomain
            : HospitalReview::CATEGORY_DOMAIN_SURGERY;
    }
}
