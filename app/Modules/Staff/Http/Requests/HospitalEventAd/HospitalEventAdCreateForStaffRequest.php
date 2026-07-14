<?php

namespace App\Modules\Staff\Http\Requests\HospitalEventAd;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

final class HospitalEventAdCreateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach (['category_id', 'manager_staff_id'] as $nullableKey) {
            if (array_key_exists($nullableKey, $data) && $data[$nullableKey] === '') {
                $data[$nullableKey] = null;
            }
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
            'hospital_id' => ['required', 'integer', Rule::exists('hospitals', 'id')->whereNull('deleted_at')],
            'hospital_event_id' => ['required', 'integer', Rule::exists('hospital_events', 'id')->whereNull('deleted_at')],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(static function ($query): void {
                    CategoryUsage::constrainActiveCategoryExistsAny($query, [
                        CategoryUsage::USAGE_HOSPITAL_EVENT_AD_SURGERY,
                        CategoryUsage::USAGE_HOSPITAL_EVENT_AD_PETIT,
                    ]);
                }),
            ],
            'manager_staff_id' => ['nullable', 'integer', Rule::exists('account_staffs', 'id')],
            'placement' => ['required', Rule::in(HospitalEventAd::placements())],
            'cost' => ['required', 'integer', 'min:0'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'ad_image_file' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $placement = $this->input('placement');
            $categoryId = $this->input('category_id');

            if (HospitalEventAd::requiresCategory($placement) && empty($categoryId)) {
                $validator->errors()->add('category_id', '부위별 광고는 카테고리를 선택해 주세요.');
            }

            if ($placement && ! HospitalEventAd::requiresCategory($placement) && ! empty($categoryId)) {
                $validator->errors()->add('category_id', '부위별 광고가 아닌 위치는 카테고리를 선택할 수 없습니다.');
            }

            if ($placement && ! empty($categoryId) && ! $this->categoryMatchesPlacement((int) $categoryId, (string) $placement)) {
                $validator->errors()->add('category_id', '광고 위치에 맞는 카테고리를 선택해 주세요.');
            }

            $startDate = $this->input('start_date');
            if (is_string($startDate) && $startDate !== '') {
                try {
                    $date = Carbon::createFromFormat('Y-m-d', $startDate);
                } catch (\Throwable) {
                    return;
                }

                if ($date->startOfDay()->lessThan(now()->startOfDay())) {
                    $validator->errors()->add('start_date', '광고 노출 시작일은 오늘 이후 날짜만 선택할 수 있습니다.');
                }

                if ($date->dayOfWeek !== CarbonInterface::TUESDAY) {
                    $validator->errors()->add('start_date', '광고 노출 시작일은 화요일만 선택할 수 있습니다.');
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'hospital_id' => '병의원',
            'hospital_event_id' => '이벤트',
            'category_id' => '카테고리',
            'manager_staff_id' => '담당자',
            'placement' => '광고위치',
            'cost' => '비용',
            'start_date' => '희망 노출 시작일',
            'ad_image_file' => '광고 이미지',
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
