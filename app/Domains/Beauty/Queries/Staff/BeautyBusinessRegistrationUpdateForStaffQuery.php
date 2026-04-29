<?php

namespace App\Domains\Beauty\Queries\Staff;

use App\Domains\Beauty\Models\Beauty;
use App\Domains\Beauty\Models\BeautyBusinessRegistration;

/**
 * BeautyBusinessRegistrationUpdateForStaffQuery 역할 정의.
 * 뷰티 사업자 등록 수정에 필요한 조회/저장을 캡슐화한다.
 */
final class BeautyBusinessRegistrationUpdateForStaffQuery
{
    public function findForBeauty(Beauty $beauty): ?BeautyBusinessRegistration
    {
        $businessRegistration = $beauty->businessRegistration()->first();

        return $businessRegistration instanceof BeautyBusinessRegistration
            ? $businessRegistration
            : null;
    }

    public function update(BeautyBusinessRegistration $businessRegistration, array $updates): BeautyBusinessRegistration
    {
        $businessRegistration->fill($updates);

        if ($businessRegistration->isDirty()) {
            $businessRegistration->save();
        }

        return $businessRegistration->fresh();
    }
}
