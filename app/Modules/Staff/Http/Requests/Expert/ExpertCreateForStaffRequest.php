<?php

namespace App\Modules\Staff\Http\Requests\Expert;

use App\Domains\Common\Models\Category\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ExpertCreateForStaffRequest 역할 정의.
 * 스태프 모듈의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class ExpertCreateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach (['educations', 'careers', 'etc_contents'] as $key) {
            if (array_key_exists($key, $data) && is_string($data[$key])) {
                $decoded = json_decode($data[$key], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data[$key] = $decoded;
                }
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
            'beauty_id' => ['required', 'integer', 'exists:beauties,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:50'],
            'career_started_at' => ['nullable', 'date'],
            'educations' => ['nullable', 'array'],
            'careers' => ['nullable', 'array'],
            'etc_contents' => ['nullable', 'array'],
            'category_ids' => ['nullable', 'array', 'min:1', 'max:100'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(static fn ($query) => $query
                    ->where('domain', Category::DOMAIN_BEAUTY)
                    ->where('status', Category::STATUS_ACTIVE)),
            ],

            'profile_image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'education_certificate_image' => ['nullable', 'array', 'max:12'],
            'education_certificate_image.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'etc_certificate_image' => ['nullable', 'array', 'max:12'],
            'etc_certificate_image.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ];
    }

    public function attributes(): array
    {
        return [
            'beauty_id' => 'Beauty ID',
            'sort_order' => 'Sort order',
            'name' => 'Expert name',
            'gender' => 'Gender',
            'position' => 'Position',
            'career_started_at' => 'Career started at',
            'educations' => 'Educations',
            'careers' => 'Careers',
            'etc_contents' => 'Etc contents',
            'category_ids' => 'Category IDs',
            'category_ids.*' => 'Category ID',
            'profile_image' => 'Profile image',
            'education_certificate_image' => 'Education certificate image',
            'education_certificate_image.*' => 'Education certificate image',
            'etc_certificate_image' => 'Etc certificate image',
            'etc_certificate_image.*' => 'Etc certificate image',
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
