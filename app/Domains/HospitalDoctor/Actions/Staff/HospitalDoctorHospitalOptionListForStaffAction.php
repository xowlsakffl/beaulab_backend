<?php

namespace App\Domains\HospitalDoctor\Actions\Staff;

use App\Domains\Hospital\Dto\Staff\HospitalOptionForStaffDto;
use App\Domains\HospitalDoctor\Queries\Staff\HospitalDoctorHospitalOptionListForStaffQuery;
use App\Domains\Hospital\Models\Hospital;
use Illuminate\Support\Facades\Gate;

/**
 * DoctorHospitalOptionListForStaffAction 역할 정의.
 * 병원 의사 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HospitalDoctorHospitalOptionListForStaffAction
{
    public function __construct(
        private readonly HospitalDoctorHospitalOptionListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', Hospital::class);

        $items = $this->query->get($filters)
            ->map(static fn (Hospital $hospital): array => HospitalOptionForStaffDto::fromModel($hospital)->toArray())
            ->values()
            ->all();

        return [
            'items' => $items,
            'meta' => null,
        ];
    }
}
