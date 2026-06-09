<?php

namespace App\Modules\Staff\Http\Requests\HospitalEvaluation;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalEvaluationListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->normalizeToArray($this->input('status')),
            'report_status' => $this->normalizeToArray($this->input('report_status')),
            'post_status' => $this->normalizeToArray($this->input('post_status')),
            'receipt_status' => $this->normalizeToArray($this->input('receipt_status')),
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
            'status.*' => [Rule::in(HospitalEvaluation::statuses())],
            'report_status' => ['nullable', 'array'],
            'report_status.*' => [Rule::in([
                ContentReportState::STATUS_AUTO_BLOCKED,
                ContentReportState::STATUS_ADMIN_HIDDEN,
                ContentReportState::STATUS_NORMAL_VISIBLE,
                ContentReportState::STATUS_REEXPOSED,
            ])],
            'post_status' => ['nullable', 'array'],
            'post_status.*' => [Rule::in(HospitalEvaluation::postStatuses())],
            'receipt_status' => ['nullable', 'array'],
            'receipt_status.*' => [Rule::in(HospitalEvaluation::receiptStatuses())],
            'author_id' => ['nullable', 'integer', 'exists:account_users,id'],
            'hospital_id' => ['nullable', 'integer', Rule::exists('hospitals', 'id')->where(static fn ($query) => $query->whereNull('deleted_at'))],
            'doctor_id' => ['nullable', 'integer', Rule::exists('hospital_doctors', 'id')->where(static fn ($query) => $query->whereNull('deleted_at'))],
            'category_ids' => ['nullable', 'array', 'min:1', 'max:3'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(fn ($query) => $query
                    ->where('domain', Category::DOMAIN_HOSPITAL_EVALUATION)
                    ->whereNull('parent_id')
                    ->where('status', Category::STATUS_ACTIVE)),
            ],
            'cost_min' => ['nullable', 'integer', 'min:0'],
            'cost_max' => ['nullable', 'integer', 'min:0'],
            'ratings' => ['nullable', 'array', 'min:1', 'max:5'],
            'ratings.*' => ['integer', Rule::in([1, 2, 3, 4, 5])],
            'view_count_min' => ['nullable', 'integer', 'min:0'],
            'view_count_max' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'sort' => ['nullable', 'in:id,cost,average_rating,view_count,status,post_status,receipt_status,created_at,updated_at'],
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
            'report_status' => $validated['report_status'] ?? null,
            'post_status' => $validated['post_status'] ?? null,
            'receipt_status' => $validated['receipt_status'] ?? null,
            'author_id' => $validated['author_id'] ?? null,
            'hospital_id' => $validated['hospital_id'] ?? null,
            'doctor_id' => $validated['doctor_id'] ?? null,
            'category_ids' => $validated['category_ids'] ?? null,
            'cost_min' => isset($validated['cost_min']) ? (int) $validated['cost_min'] : null,
            'cost_max' => isset($validated['cost_max']) ? (int) $validated['cost_max'] : null,
            'ratings' => $validated['ratings'] ?? null,
            'view_count_min' => isset($validated['view_count_min']) ? (int) $validated['view_count_min'] : null,
            'view_count_max' => isset($validated['view_count_max']) ? (int) $validated['view_count_max'] : null,
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
            'report_status' => '상태',
            'report_status.*' => '상태',
            'post_status' => '게시 상태',
            'post_status.*' => '게시 상태',
            'receipt_status' => '영수증 상태',
            'receipt_status.*' => '영수증 상태',
            'author_id' => '작성자',
            'hospital_id' => '병의원',
            'doctor_id' => '의료진',
            'category_ids' => '카테고리 목록',
            'category_ids.*' => '카테고리',
            'cost_min' => '최소 비용',
            'cost_max' => '최대 비용',
            'ratings' => '평점 목록',
            'ratings.*' => '평점',
            'view_count_min' => '최소 조회수',
            'view_count_max' => '최대 조회수',
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
}
