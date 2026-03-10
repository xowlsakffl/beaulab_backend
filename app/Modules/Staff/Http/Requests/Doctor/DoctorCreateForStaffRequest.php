<?php

namespace App\Modules\Staff\Http\Requests\Doctor;

use App\Domains\Common\Models\Category\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DoctorCreateForStaffRequest extends FormRequest
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
            'hospital_id' => ['required', 'integer', 'exists:hospitals,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:50'],
            'career_started_at' => ['nullable', 'date'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'is_specialist' => ['nullable', 'boolean'],
            'educations' => ['nullable', 'array'],
            'careers' => ['nullable', 'array'],
            'etc_contents' => ['nullable', 'array'],
            'category_ids' => ['nullable', 'array', 'min:1', 'max:100'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(static fn ($query) => $query
                    ->whereIn('domain', [Category::DOMAIN_HOSPITAL_TREATMENT, Category::DOMAIN_HOSPITAL_SURGERY])
                    ->where('status', Category::STATUS_ACTIVE)),
            ],

            'profile_image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'license_image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],

            'specialist_certificate_image' => ['nullable', 'array', 'max:12'],
            'specialist_certificate_image.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'education_certificate_image' => ['nullable', 'array', 'max:12'],
            'education_certificate_image.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'etc_certificate_image' => ['nullable', 'array', 'max:12'],
            'etc_certificate_image.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ];
    }

    public function attributes(): array
    {
        return [
            'hospital_id' => "병원 ID",
            'sort_order' => "정렬 순서",
            'name' => '의사명',
            'gender' => '성별',
            'position' => "직책",
            'career_started_at' => '경력 시작일',
            'license_number' => '면허증 번호',
            'is_specialist' => "전문의 여부",
            'educations' => '학력 사항',
            'careers' => '경력 사항',
            'etc_contents' => '기타 사항',
            'category_ids' => '카테고리 목록',
            'category_ids.*' => '카테고리',

            'profile_image' => '프로필 이미지',
            'license_image' => '면허증 이미지',

            'specialist_certificate_image' => '전문의 면허증 이미지',
            'specialist_certificate_image.*' => '전문의 면허증 이미지',
            'education_certificate_image' => '학력 증명서 이미지',
            'education_certificate_image.*' => '학력 증명서 이미지',
            'etc_certificate_image' => '기타 증명서 이미지',
            'etc_certificate_image.*' => '기타 증명서 이미지',
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
