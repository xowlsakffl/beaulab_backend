<?php

namespace App\Modules\User\Http\Requests\HospitalReview;

use App\Domains\Common\Models\Category\Category;
use App\Domains\HospitalReview\Models\HospitalReview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * HospitalReviewCreateForUserRequest 역할 정의.
 * 병의원 후기 도메인의 HTTP 요청 검증 객체로, 사용자 입력의 형식과 기본 제약을 검증한다.
 */
final class HospitalReviewCreateForUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (array_key_exists('doctor_id', $data) && $data['doctor_id'] === '') {
            $data['doctor_id'] = null;
        }

        if (array_key_exists('category_codes', $data)) {
            $data['category_codes'] = $this->normalizeCodeList($data['category_codes']);
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
            'hospital_id' => ['required', 'integer', Rule::exists('hospitals', 'id')->where(static fn ($query) => $query->whereNull('deleted_at'))],
            'doctor_id' => ['nullable', 'integer', Rule::exists('hospital_doctors', 'id')->where(static fn ($query) => $query->whereNull('deleted_at'))],
            'category_domain' => ['required', Rule::in(HospitalReview::categoryDomains())],
            'category_codes' => ['required', 'array', 'min:1', 'max:' . HospitalReview::MAX_CATEGORY_COUNT],
            'category_codes.*' => [
                'required',
                'string',
                'max:80',
                'distinct:strict',
                Rule::exists('categories', 'code')->where(fn ($query) => $query
                    ->where('domain', (string) $this->input('category_domain'))
                    ->where('status', Category::STATUS_ACTIVE)),
            ],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:20000'],
            'cost' => ['required', 'integer', 'min:0'],
            'rating' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5])],
            'before_images' => ['nullable', 'array', 'max:' . HospitalReview::MAX_BEFORE_IMAGE_COUNT],
            'before_images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'after_images' => ['nullable', 'array', 'max:' . HospitalReview::MAX_AFTER_IMAGE_COUNT],
            'after_images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }

    public function attributes(): array
    {
        return [
            'hospital_id' => '병의원',
            'doctor_id' => '의료진',
            'category_domain' => '후기 유형',
            'category_codes' => '카테고리 목록',
            'category_codes.*' => '카테고리',
            'title' => '제목',
            'content' => '내용',
            'cost' => '시술/수술 비용',
            'rating' => '평점',
            'before_images' => '시술 전 사진 목록',
            'before_images.*' => '시술 전 사진',
            'after_images' => '시술 후 사진 목록',
            'after_images.*' => '시술 후 사진',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function normalizeCodeList(mixed $value): array
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
            ->map(static fn ($item): ?string => is_string($item) ? trim($item) : null)
            ->filter(static fn (?string $item): bool => $item !== null && $item !== '')
            ->values()
            ->all();
    }
}
