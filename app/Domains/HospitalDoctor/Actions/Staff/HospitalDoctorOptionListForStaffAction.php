<?php

namespace App\Domains\HospitalDoctor\Actions\Staff;

use App\Domains\HospitalDoctor\Dto\Staff\HospitalDoctorOptionForStaffDto;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalDoctor\Queries\Staff\HospitalDoctorOptionListForStaffQuery;
use Illuminate\Support\Facades\Gate;

/**
 * HospitalDoctorOptionListForStaffAction 역할 정의.
 * 의료진 관리 화면의 의료진명 자동완성 조회 유스케이스를 처리한다.
 */
final class HospitalDoctorOptionListForStaffAction
{
    public function __construct(
        private readonly HospitalDoctorOptionListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalDoctor::class);

        $items = $this->query->get($filters)
            ->map(static fn (HospitalDoctor $doctor): array => HospitalDoctorOptionForStaffDto::fromModel($doctor)->toArray())
            ->values()
            ->all();

        return [
            'items' => $items,
            'meta' => null,
        ];
    }
}
