<?php

namespace App\Domains\Common\Category\Support;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Category\Models\Category;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;

final class CategoryChangeGuard
{
    public function assertNoPaidAds(Category $category): void
    {
        if ($category->domain !== Category::DOMAIN_HOSPITAL_MEDICAL) {
            return;
        }
        $ids = CategoryHierarchy::descendants($category);
        $exists = HospitalEventAd::query()->where('cost', '>', 0)->where('allow_status', HospitalEventAd::ALLOW_APPROVED)
            ->where('end_at', '>=', now())->where(fn ($q) => $q->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $ids))
            ->orWhereHas('hospitalEvent.categories', fn ($c) => $c->whereIn('categories.id', $ids)))->exists();
        if ($exists) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '예약·진행 중인 유료 광고가 연결되어 있어 사용 중지 또는 메뉴 숨김을 할 수 없습니다.');
        }
    }
}
