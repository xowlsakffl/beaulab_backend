<?php

namespace App\Domains\HospitalEventAd\Support;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Common\Category\Support\CategoryUsageAvailability;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;

final class HospitalEventAdCategoryGuard
{
    public function assertAvailable(int $eventId, string $placement, ?int $categoryId): void
    {
        $categories = Category::query()->domain(Category::DOMAIN_HOSPITAL_MEDICAL)->with('usages')->get();
        $resolved = app(CategoryUsageAvailability::class)->resolve($categories);
        $eventCategories = HospitalEvent::findOrFail($eventId)->categories()->get();
        foreach ($eventCategories as $category) {
            $usage = CategoryUsage::eventUsageForGroup($category->group_code);
            if (! ($resolved[$category->id]['usages'][$usage]['visible'] ?? false)) {
                $this->fail();
            }
        }
        if ($eventCategories->isEmpty()) {
            $this->fail();
        }
        if (HospitalEventAd::requiresCategory($placement)) {
            $usage = HospitalEventAd::categoryUsageForPlacement($placement);
            if (! ($resolved[$categoryId]['usages'][$usage]['visible'] ?? false)) {
                $this->fail();
            }
        }
    }

    private function fail(): never
    {
        throw new CustomException(ErrorCode::INVALID_REQUEST, '사용 중지되었거나 메뉴에서 숨겨진 카테고리가 있어 광고를 승인할 수 없습니다. 카테고리 설정을 먼저 확인해 주세요.');
    }
}
