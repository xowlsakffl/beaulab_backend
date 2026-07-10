<?php

namespace App\Modules\Staff\Http\Requests\HospitalVideo;

use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalVideoListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'hospital_status' => $this->normalizeToArray($this->input('hospital_status')),
            'admin_status' => $this->normalizeToArray($this->input('admin_status')),
            'report_status' => $this->normalizeToArray($this->input('report_status')),
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
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(static function ($query): void {
                    CategoryUsage::constrainActiveCategoryExists($query, CategoryUsage::USAGE_HOSPITAL_VIDEO_CATEGORY);
                }),
            ],
            'q' => ['nullable', 'string', 'max:100'],
            'hospital_status' => ['nullable', 'array'],
            'hospital_status.*' => [Rule::in(HospitalVideo::hospitalStatuses())],
            'admin_status' => ['nullable', 'array'],
            'admin_status.*' => [Rule::in(HospitalVideo::adminStatuses())],
            'report_status' => ['nullable', 'array'],
            'report_status.*' => [Rule::in(ContentReportState::statuses())],
            'report_count_min' => ['nullable', 'integer', 'min:0'],
            'report_count_max' => ['nullable', 'integer', 'min:0'],
            'view_count_min' => ['nullable', 'integer', 'min:0'],
            'view_count_max' => ['nullable', 'integer', 'min:0'],
            'like_count_min' => ['nullable', 'integer', 'min:0'],
            'like_count_max' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'sort' => ['nullable', 'in:id,title,hospital_status,admin_status,view_count,like_count,created_at,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'hospital_id' => $validated['hospital_id'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'q' => $validated['q'] ?? null,
            'hospital_status' => $validated['hospital_status'] ?? null,
            'admin_status' => $validated['admin_status'] ?? null,
            'report_status' => $validated['report_status'] ?? null,
            'report_count_min' => $validated['report_count_min'] ?? null,
            'report_count_max' => $validated['report_count_max'] ?? null,
            'view_count_min' => $validated['view_count_min'] ?? null,
            'view_count_max' => $validated['view_count_max'] ?? null,
            'like_count_min' => $validated['like_count_min'] ?? null,
            'like_count_max' => $validated['like_count_max'] ?? null,
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
            'hospital_id' => '병의원',
            'category_id' => '카테고리',
            'q' => '검색어',
            'hospital_status' => '공개여부',
            'admin_status' => '강제중지',
            'report_status' => '신고상태',
            'report_count_min' => '신고횟수 최소값',
            'report_count_max' => '신고횟수 최대값',
            'view_count_min' => '조회수 최소값',
            'view_count_max' => '조회수 최대값',
            'like_count_min' => '좋아요수 최소값',
            'like_count_max' => '좋아요수 최대값',
            'start_date' => '등록일 시작',
            'end_date' => '등록일 종료',
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
        }

        if (! is_array($value)) {
            return null;
        }

        $normalized = array_values(array_filter(array_map(
            static fn ($item) => is_string($item) ? trim($item) : null,
            $value,
        )));

        return $normalized === [] ? null : $normalized;
    }
}
