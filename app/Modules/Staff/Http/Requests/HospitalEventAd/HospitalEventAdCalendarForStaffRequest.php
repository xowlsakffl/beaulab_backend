<?php

namespace App\Modules\Staff\Http\Requests\HospitalEventAd;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalEventAdCalendarForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (($data['category_id'] ?? null) === '') {
            $data['category_id'] = null;
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
            'group' => ['required', Rule::in(HospitalEventAd::placementGroupKeys())],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(static function ($query): void {
                    CategoryUsage::constrainActiveCategoryExistsAny($query, [
                        CategoryUsage::USAGE_HOSPITAL_EVENT_AD_SURGERY,
                        CategoryUsage::USAGE_HOSPITAL_EVENT_AD_TREATMENT,
                    ]);
                }),
            ],
            'month' => ['required', 'date_format:Y-m'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $group = $this->input('group');
            $categoryId = $this->input('category_id');
            $usage = is_string($group) ? HospitalEventAd::categoryUsageForGroup($group) : null;

            if ($usage === null && ! empty($categoryId)) {
                $validator->errors()->add('category_id', '카테고리별 배너가 아닌 탭은 카테고리를 선택할 수 없습니다.');

                return;
            }

            if ($usage !== null && ! empty($categoryId) && ! $this->categoryMatchesUsage((int) $categoryId, $usage)) {
                $validator->errors()->add('category_id', '광고 탭에 맞는 카테고리를 선택해 주세요.');
            }
        });
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'group' => $validated['group'],
            'category_id' => $validated['category_id'] ?? null,
            'month' => $validated['month'],
        ];
    }

    public function attributes(): array
    {
        return [
            'group' => '광고 탭',
            'category_id' => '카테고리',
            'month' => '조회월',
        ];
    }

    private function categoryMatchesUsage(int $categoryId, string $usage): bool
    {
        return Category::query()
            ->whereKey($categoryId)
            ->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)
            ->where('status', Category::STATUS_ACTIVE)
            ->whereHas('usages', static fn ($query) => $query
                ->where('usage', $usage)
                ->where('status', CategoryUsage::STATUS_ACTIVE))
            ->exists();
    }
}
