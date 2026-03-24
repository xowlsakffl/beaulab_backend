<?php

namespace App\Modules\Staff\Http\Requests\HospitalVideo;

use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Foundation\Http\FormRequest;

final class HospitalVideoListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->normalizeToArray($this->input('status')),
            'allow_status' => $this->normalizeToArray($this->input('allow_status')),
            'distribution_channel' => $this->normalizeToArray($this->input('distribution_channel')),
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
            'status.*' => ['in:'.implode(',', [HospitalVideo::STATUS_ACTIVE, HospitalVideo::STATUS_INACTIVE])],
            'allow_status' => ['nullable', 'array'],
            'allow_status.*' => ['in:'.implode(',', [
                HospitalVideo::ALLOW_STATUS_SUBMITTED,
                HospitalVideo::ALLOW_STATUS_IN_REVIEW,
                HospitalVideo::ALLOW_STATUS_APPROVED,
                HospitalVideo::ALLOW_STATUS_REJECTED,
                HospitalVideo::ALLOW_STATUS_EXCLUDED,
                HospitalVideo::ALLOW_STATUS_PARTNER_CANCELED,
            ])],
            'distribution_channel' => ['nullable', 'array'],
            'distribution_channel.*' => ['in:'.implode(',', [
                HospitalVideo::DISTRIBUTION_CHANNEL_YOUTUBE_APP,
                HospitalVideo::DISTRIBUTION_CHANNEL_APP,
            ])],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'allowed_start_date' => ['nullable', 'date'],
            'allowed_end_date' => ['nullable', 'date', 'after_or_equal:allowed_start_date'],
            'sort' => ['nullable', 'in:id,title,status,allow_status,distribution_channel,view_count,like_count,created_at,allowed_at,updated_at'],
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
            'distribution_channel' => $validated['distribution_channel'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'allowed_start_date' => $validated['allowed_start_date'] ?? null,
            'allowed_end_date' => $validated['allowed_end_date'] ?? null,
            'sort' => $validated['sort'] ?? 'id',
            'direction' => $validated['direction'] ?? 'desc',
            'per_page' => (int) ($validated['per_page'] ?? 15),
        ];
    }

    public function attributes(): array
    {
        return [
            'hospital_id' => '병원',
            'q' => '검색어',
            'status' => '운영 상태',
            'status.*' => '운영 상태',
            'allow_status' => '검수 상태 목록',
            'allow_status.*' => '검수 상태',
            'distribution_channel' => '배포 채널 목록',
            'distribution_channel.*' => '배포 채널',
            'start_date' => '등록일 시작',
            'end_date' => '등록일 종료',
            'allowed_start_date' => '검수 처리 시각 시작',
            'allowed_end_date' => '검수 처리 시각 종료',
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
            $value = explode(',', $value);
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
