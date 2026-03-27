<?php

namespace App\Modules\Staff\Http\Requests\Hashtag;

use App\Domains\Common\Models\Hashtag\Hashtag;
use Illuminate\Foundation\Http\FormRequest;

final class HashtagListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $q = $this->input('q');
        $status = $this->input('status');
        $startDate = $this->input('start_date');
        $endDate = $this->input('end_date');
        $updatedStartDate = $this->input('updated_start_date');
        $updatedEndDate = $this->input('updated_end_date');

        $this->merge([
            'q' => is_string($q)
                ? trim((string) (preg_replace('/^[#＃]+/u', '', trim($q)) ?? trim($q)))
                : $q,
            'status' => is_string($status) ? trim($status) : $status,
            'start_date' => is_string($startDate) ? trim($startDate) : $startDate,
            'end_date' => is_string($endDate) ? trim($endDate) : $endDate,
            'updated_start_date' => is_string($updatedStartDate) ? trim($updatedStartDate) : $updatedStartDate,
            'updated_end_date' => is_string($updatedEndDate) ? trim($updatedEndDate) : $updatedEndDate,
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
            'status' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'updated_start_date' => ['nullable', 'date_format:Y-m-d'],
            'updated_end_date' => ['nullable', 'date_format:Y-m-d'],
            'sort' => ['nullable', 'in:id,name,normalized_name,status,usage_count,created_at,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();
        $statuses = collect(explode(',', (string) ($validated['status'] ?? '')))
            ->map(static fn (string $value): string => strtoupper(trim($value)))
            ->filter(static fn (string $value): bool => Hashtag::isValidStatus($value))
            ->unique()
            ->values()
            ->all();

        return [
            'q' => $validated['q'] ?? null,
            'statuses' => $statuses,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'updated_start_date' => $validated['updated_start_date'] ?? null,
            'updated_end_date' => $validated['updated_end_date'] ?? null,
            'sort' => $validated['sort'] ?? 'id',
            'direction' => $validated['direction'] ?? 'desc',
            'per_page' => (int) ($validated['per_page'] ?? 50),
        ];
    }

    public function attributes(): array
    {
        return [
            'q' => '검색어',
            'status' => '운영상태',
            'start_date' => '등록 시작일',
            'end_date' => '등록 종료일',
            'updated_start_date' => '수정 시작일',
            'updated_end_date' => '수정 종료일',
            'sort' => '정렬 기준',
            'direction' => '정렬 방향',
            'page' => '페이지',
            'per_page' => '페이지당 개수',
        ];
    }
}
