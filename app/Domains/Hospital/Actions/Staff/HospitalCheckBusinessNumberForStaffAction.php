<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Actions\Staff;

use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Queries\Staff\HospitalBusinessNumberExistsForStaffQuery;
use Illuminate\Support\Facades\Gate;

/**
 * HospitalCheckBusinessNumberForStaffAction 역할 정의.
 * 병원 사업자등록번호 중복 확인 유스케이스를 담당하며, 권한 확인과 Query 호출 결과를 응답 구조로 정리한다.
 */
final class HospitalCheckBusinessNumberForStaffAction
{
    public function __construct(
        private readonly HospitalBusinessNumberExistsForStaffQuery $query,
    ) {}

    public function execute(string $businessNumber): array
    {
        Gate::authorize('create', Hospital::class);

        $exists = $this->query->exists($businessNumber);

        return [
            'exists' => $exists,
            'available' => ! $exists,
            'business_number' => $businessNumber,
        ];
    }
}
