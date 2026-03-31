<?php

namespace App\Modules\Staff\Http\Requests\Talk;

use App\Domains\Common\Models\Category\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TalkUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach ([
            'author_id',
            'title',
            'content',
            'status',
            'is_visible',
            'author_ip',
            'is_pinned',
            'pinned_order',
            'admin_note',
        ] as $nullableKey) {
            if (array_key_exists($nullableKey, $data) && $data[$nullableKey] === '') {
                $data[$nullableKey] = null;
            }
        }

        if (array_key_exists('category_ids', $data)) {
            $data['category_ids'] = $this->normalizeIdList($data['category_ids']);
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
            'author_id' => ['sometimes', 'nullable', 'integer', 'exists:account_users,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'status' => ['sometimes', 'nullable', 'in:ACTIVE,INACTIVE'],
            'is_visible' => ['sometimes', 'nullable', 'boolean'],
            'author_ip' => ['sometimes', 'nullable', 'ip'],
            'is_pinned' => ['sometimes', 'nullable', 'boolean'],
            'pinned_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'category_ids' => ['sometimes', 'array', 'max:100'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(static fn ($query) => $query
                    ->where('domain', Category::DOMAIN_HOSPITAL_COMMUNITY)
                    ->where('status', Category::STATUS_ACTIVE)),
            ],
            'images' => ['sometimes', 'array', 'max:20'],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'admin_note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'author_id' => '작성자',
            'title' => '제목',
            'content' => '내용',
            'status' => '운영 상태',
            'is_visible' => '노출 여부',
            'author_ip' => '작성자 IP',
            'is_pinned' => '상단 고정 여부',
            'pinned_order' => '고정 정렬 순서',
            'category_ids' => '카테고리 목록',
            'category_ids.*' => '카테고리',
            'images' => '이미지 목록',
            'images.*' => '이미지',
            'admin_note' => '관리자 메모',
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
            ->values()
            ->all();
    }
}
