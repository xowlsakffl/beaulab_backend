<?php

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalVideo\Queries\Staff\HospitalVideoDoctorOptionListForStaffQuery;
use Illuminate\Support\Facades\Gate;

/**
 * HospitalVideoDoctorOptionListForStaffAction 역할 정의.
 * 병원 동영상 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HospitalVideoDoctorOptionListForStaffAction
{
    public function __construct(
        private readonly HospitalVideoDoctorOptionListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalDoctor::class);

        $items = $this->query->get($filters)
            ->map(static fn (HospitalDoctor $doctor): array => [
                'id' => (int) $doctor->id,
                'name' => (string) $doctor->name,
                'position' => $doctor->position,
            ])
            ->values()
            ->all();

        return [
            'items' => $items,
            'meta' => null,
        ];
    }
}
