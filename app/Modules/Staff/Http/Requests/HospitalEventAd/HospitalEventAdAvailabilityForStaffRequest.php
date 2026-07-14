<?php

namespace App\Modules\Staff\Http\Requests\HospitalEventAd;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalEventAdAvailabilityForStaffRequest extends FormRequest
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
            'placement' => ['required', Rule::in(HospitalEventAd::placements())],
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
            $placement = $this->input('placement');
            $categoryId = $this->input('category_id');

            if (! is_string($placement) || $placement === '') {
                return;
            }

            if (HospitalEventAd::requiresCategory($placement) && empty($categoryId)) {
                $validator->errors()->add('category_id', '카테고리별 배너는 카테고리를 선택해 주세요.');

                return;
            }

            if (! HospitalEventAd::requiresCategory($placement) && ! empty($categoryId)) {
                $validator->errors()->add('category_id', '카테고리별 배너가 아닌 위치는 카테고리를 선택할 수 없습니다.');

                return;
            }

            if (! empty($categoryId) && ! $this->categoryMatchesPlacement((int) $categoryId, $placement)) {
                $validator->errors()->add('category_id', '광고 위치에 맞는 카테고리를 선택해 주세요.');
            }
        });
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'placement' => $validated['placement'],
            'category_id' => $validated['category_id'] ?? null,
            'month' => $validated['month'],
        ];
    }

    public function attributes(): array
    {
        return [
            'placement' => '광고위치',
            'category_id' => '카테고리',
            'month' => '조회월',
        ];
    }

    private function categoryMatchesPlacement(int $categoryId, string $placement): bool
    {
        $usage = HospitalEventAd::categoryUsageForPlacement($placement);
        if ($usage === null) {
            return true;
        }

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
