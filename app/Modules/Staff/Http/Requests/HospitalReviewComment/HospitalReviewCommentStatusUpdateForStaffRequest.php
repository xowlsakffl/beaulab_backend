<?php

namespace App\Modules\Staff\Http\Requests\HospitalReviewComment;

use App\Domains\HospitalReview\Models\HospitalReviewComment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalReviewCommentStatusUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (array_key_exists('ids', $data)) {
            $data['ids'] = $this->normalizeIdList($data['ids']);
        }

        $this->replace($data);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => [
                'integer',
                'distinct',
                Rule::exists('hospital_review_comments', 'id')->whereNull('deleted_at'),
            ],
            'status' => ['required', Rule::in(HospitalReviewComment::statuses())],
            'hidden_reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'ids' => '병의원 후기 댓글 목록',
            'ids.*' => '병의원 후기 댓글',
            'status' => '노출 상태',
            'hidden_reason' => '미노출 사유',
        ];
    }

    /**
     * @return array<int, int>
     */
    private function normalizeIdList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(static fn ($item): bool => is_int($item) || (is_string($item) && ctype_digit(trim($item))))
            ->map(static fn ($item): int => (int) $item)
            ->filter(static fn (int $item): bool => $item > 0)
            ->unique()
            ->values()
            ->all();
    }
}
