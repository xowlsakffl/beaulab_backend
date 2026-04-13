<?php

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalVideo\Queries\Staff\HospitalVideoHospitalOptionListForStaffQuery;
use Illuminate\Support\Facades\Gate;

/**
 * HospitalVideoHospitalOptionListForStaffAction 역할 정의.
 * 병원 동영상 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HospitalVideoHospitalOptionListForStaffAction
{
    public function __construct(
        private readonly HospitalVideoHospitalOptionListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', Hospital::class);

        $items = $this->query->get($filters)
            ->map(static fn (Hospital $hospital): array => [
                'id' => (int) $hospital->id,
                'name' => (string) $hospital->name,
                'business_number' => $hospital->businessRegistration?->business_number,
            ])
            ->values()
            ->all();

        return [
            'items' => $items,
            'meta' => null,
        ];
    }
}
