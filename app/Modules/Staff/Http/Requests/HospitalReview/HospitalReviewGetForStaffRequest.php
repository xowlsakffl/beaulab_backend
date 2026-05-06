<?php

namespace App\Modules\Staff\Http\Requests\HospitalReview;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * HospitalReviewGetForStaffRequest 역할 정의.
 * 병원후기 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
class HospitalReviewGetForStaffRequest extends FormRequest
{

    protected function prepareForValidation(): void
    {
        $this->merge([
            'comments_page' => $this->normalizePositiveInt($this->input('comments_page')),
            'comments_per_page' => $this->normalizePositiveInt($this->input('comments_per_page')),
            'operation_histories_page' => $this->normalizePositiveInt($this->input('operation_histories_page')),
            'operation_histories_per_page' => $this->normalizePositiveInt($this->input('operation_histories_per_page')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comments_page' => ['nullable', 'integer', 'min:1'],
            'comments_per_page' => ['nullable', 'integer', Rule::in([10, 20, 50])],
            'operation_histories_page' => ['nullable', 'integer', 'min:1'],
            'operation_histories_per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'comments_page' => (int) ($validated['comments_page'] ?? 1),
            'comments_per_page' => (int) ($validated['comments_per_page'] ?? 10),
            'operation_histories_page' => (int) ($validated['operation_histories_page'] ?? 1),
            'operation_histories_per_page' => (int) ($validated['operation_histories_per_page'] ?? 15),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'comments_page' => '댓글 페이지',
            'comments_per_page' => '댓글 페이지당 개수',
            'operation_histories_page' => '운영 히스토리 페이지',
            'operation_histories_per_page' => '운영 히스토리 페이지당 개수',
        ];
    }

    private function normalizePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_string($value) && ctype_digit(trim($value))) {
            $normalized = (int) $value;

            return $normalized > 0 ? $normalized : null;
        }

        return null;
    }
}
