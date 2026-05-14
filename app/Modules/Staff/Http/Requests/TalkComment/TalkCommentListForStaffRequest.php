<?php

namespace App\Modules\Staff\Http\Requests\TalkComment;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TalkCommentListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->normalizeToArray($this->input('status')),
            'category_ids' => $this->normalizeToArray($this->input('category_ids') ?? $this->input('category_id')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'talk_id' => ['nullable', 'integer', 'exists:talks,id'],
            'parent_id' => ['nullable', 'integer', 'min:0'],
            'author_id' => ['nullable', 'integer', 'exists:account_users,id'],
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'array'],
            'status.*' => [Rule::in(TalkComment::statuses())],
            'category_ids' => ['nullable', 'array', 'min:1', 'max:100'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(static fn ($query) => $query
                    ->where('domain', Talk::CATEGORY_DOMAIN)
                    ->where('status', Category::STATUS_ACTIVE)),
            ],
            'metric_min' => ['nullable', 'integer', 'min:0'],
            'metric_max' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'sort' => ['nullable', 'in:id,status,like_count,created_at,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'talk_id' => $validated['talk_id'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'author_id' => $validated['author_id'] ?? null,
            'q' => $validated['q'] ?? null,
            'status' => $validated['status'] ?? null,
            'category_ids' => $validated['category_ids'] ?? null,
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
            'talk_id' => '토크',
            'parent_id' => '부모 댓글',
            'author_id' => '작성자',
            'q' => '검색어',
            'status' => '노출 여부',
            'status.*' => '노출 여부',
            'category_ids' => '토크 유형',
            'category_ids.*' => '토크 유형',
            'metric_min' => '좋아요 수 최소값',
            'metric_max' => '좋아요 수 최대값',
            'start_date' => '작성 시작일',
            'end_date' => '작성 종료일',
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
