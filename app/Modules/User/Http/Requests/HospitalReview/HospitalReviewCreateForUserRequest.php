<?php

namespace App\Modules\User\Http\Requests\HospitalReview;

use App\Domains\Common\Category\Models\Category;
use App\Domains\HospitalReview\Models\HospitalReview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'category_codes' => ['required', 'array', 'min:1', 'max:'.HospitalReview::MAX_CATEGORY_COUNT],
            'category_codes.*' => [
                'required',
                'string',
                'max:80',
                'distinct:strict',
                Rule::exists('categories', 'code')->where(static fn ($query) => $query
                    ->whereIn('domain', HospitalReview::categoryDomains())
                    ->where('status', Category::STATUS_ACTIVE)),
            ],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:20000'],
            'cost' => ['required', 'integer', 'min:0'],
            'rating' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5])],
            'before_images' => ['nullable', 'array', 'max:'.HospitalReview::MAX_BEFORE_IMAGE_COUNT],
            'before_images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'after_images' => ['nullable', 'array', 'max:'.HospitalReview::MAX_AFTER_IMAGE_COUNT],
            'after_images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }

    public function attributes(): array
    {
        return [
            'hospital_id' => '병의원',
            'doctor_id' => '의료진',
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

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->count() > 0) {
                    return;
                }

                $this->validateReviewCategories($validator);
            },
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
            $trimmed = trim($value);
            $decoded = json_decode($trimmed, true);
            $value = str_starts_with($trimmed, '[')
                && json_last_error() === JSON_ERROR_NONE
                && is_array($decoded)
                && array_is_list($decoded)
                ? $decoded
                : explode(',', $trimmed);
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

    private function validateReviewCategories(Validator $validator): void
    {
        $codes = $this->input('category_codes', []);
        if (! is_array($codes) || $codes === []) {
            return;
        }

        $categories = Category::query()
            ->whereIn('domain', HospitalReview::categoryDomains())
            ->where('status', Category::STATUS_ACTIVE)
            ->whereIn('code', $codes)
            ->withCount('children')
            ->get();

        if ($categories->count() !== count($codes) || $categories->pluck('domain')->unique()->count() !== 1) {
            $validator->errors()->add('category_codes', '후기 카테고리 유형을 확인해 주세요.');

            return;
        }

        if ($categories->contains(static fn (Category $category): bool => (int) $category->depth !== 3 || (int) ($category->children_count ?? 0) > 0)) {
            $validator->errors()->add('category_codes', '후기 카테고리는 소분류만 선택할 수 있습니다.');
        }
    }
}
