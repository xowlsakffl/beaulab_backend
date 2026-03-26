<?php

namespace App\Modules\Staff\Http\Requests\Notice;

use App\Domains\Notice\Models\Notice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class NoticeListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'channel' => $this->normalizeToArray($this->input('channel')),
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
            'channel' => ['nullable', 'array'],
            'channel.*' => [Rule::in(Notice::channels())],
            'status' => ['nullable', 'array'],
            'status.*' => [Rule::in(Notice::statuses())],
            'is_pinned' => ['nullable', 'boolean'],
            'is_important' => ['nullable', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'updated_start_date' => ['nullable', 'date'],
            'updated_end_date' => ['nullable', 'date', 'after_or_equal:updated_start_date'],
            'sort' => ['nullable', 'in:id,title,channel,status,is_pinned,publish_start_at,publish_end_at,is_important,view_count,created_at,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'q' => $validated['q'] ?? null,
            'channel' => $validated['channel'] ?? null,
            'status' => $validated['status'] ?? null,
            'is_pinned' => $validated['is_pinned'] ?? null,
            'is_important' => $validated['is_important'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'updated_start_date' => $validated['updated_start_date'] ?? null,
            'updated_end_date' => $validated['updated_end_date'] ?? null,
            'sort' => $validated['sort'] ?? null,
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
            'channel' => '공지 채널 목록',
            'channel.*' => '공지 채널',
            'status' => '상태 목록',
            'status.*' => '상태',
            'is_pinned' => '상단 공지 여부',
            'is_important' => '관리자 메인 팝업 여부',
            'start_date' => '등록 시작일',
            'end_date' => '등록 종료일',
            'updated_start_date' => '수정 시작일',
            'updated_end_date' => '수정 종료일',
            'sort' => '정렬 기준',
            'direction' => '정렬 방향',
            'per_page' => '페이지당 개수',
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
            static fn ($item): ?string => is_scalar($item) ? trim((string) $item) : null,
            $value,
        )));

        return $normalized === [] ? null : array_values(array_unique($normalized));
    }
}
