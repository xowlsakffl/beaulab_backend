<?php

namespace App\Modules\Staff\Http\Requests\ContentReport;

use App\Domains\Common\ContentReport\Models\ContentReport;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\HospitalReview\Models\HospitalReview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReportedContentListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
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
            'q' => ['nullable', 'string', 'max:100'],
            'target_author_id' => ['nullable', 'integer', 'exists:account_users,id'],
            'search_type' => ['nullable', Rule::in(['all', 'id', 'nickname', 'hospital_name', 'content'])],
            'date_type' => ['nullable', Rule::in(['created_at', 'first_reported_at', 'last_message_at'])],
            'report_reason' => ['nullable', Rule::in(ContentReport::reasons())],
            'report_count_min' => ['nullable', 'integer', 'min:0'],
            'report_count_max' => ['nullable', 'integer', 'min:0'],
            'target_status' => ['nullable', Rule::in(['ACTIVE', 'INACTIVE'])],
            'warning_status' => ['nullable', Rule::in(ContentReportState::warningStatuses())],
            'report_status' => ['nullable', 'array'],
            'report_status.*' => [Rule::in(ContentReportState::statuses())],
            'category_domain' => ['nullable', Rule::in(HospitalReview::categoryDomains())],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'sort' => ['nullable', 'in:target_id,report_status,report_count,recent_hour_report_count,first_reported_at,last_reported_at,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(?string $categoryDomain = null): array
    {
        $validated = $this->validated();

        return [
            'q' => $validated['q'] ?? null,
            'target_author_id' => $validated['target_author_id'] ?? null,
            'search_type' => $validated['search_type'] ?? null,
            'date_type' => $validated['date_type'] ?? 'first_reported_at',
            'report_reason' => $validated['report_reason'] ?? null,
            'report_count_min' => isset($validated['report_count_min']) ? (int) $validated['report_count_min'] : null,
            'report_count_max' => isset($validated['report_count_max']) ? (int) $validated['report_count_max'] : null,
            'target_status' => $validated['target_status'] ?? null,
            'warning_status' => $validated['warning_status'] ?? null,
            'report_status' => $validated['report_status'] ?? null,
            'category_domain' => $categoryDomain ?? ($validated['category_domain'] ?? null),
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'sort' => $validated['sort'] ?? 'first_reported_at',
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
            'target_author_id' => '작성자',
            'search_type' => '검색 유형',
            'date_type' => '기간 기준',
            'report_reason' => '신고 사유',
            'report_count_min' => '최소 신고 수',
            'report_count_max' => '최대 신고 수',
            'target_status' => '노출 여부',
            'warning_status' => '경고 처리 상태',
            'report_status' => '신고 처리 상태',
            'report_status.*' => '신고 처리 상태',
            'category_domain' => '카테고리 도메인',
            'start_date' => '신고 시작일',
            'end_date' => '신고 종료일',
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
