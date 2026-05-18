<?php

namespace App\Domains\HospitalDoctor\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\HospitalDoctor\Dto\Staff\HospitalDoctorForStaffDto;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalDoctor\Queries\Staff\HospitalDoctorListForStaffQuery;
use Illuminate\Support\Facades\Gate;

/**
 * HospitalDoctorListForStaffAction 역할 정의.
 * 병원 의사 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HospitalDoctorListForStaffAction
{
    public function __construct(private readonly HospitalDoctorListForStaffQuery $query) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalDoctor::class);

        $paginator = $this->query->paginate($filters);

        return PaginatedResponse::fromPaginator(
            $paginator,
            fn ($doctor): array => HospitalDoctorForStaffDto::fromModel($doctor)->toArray(),
        );
    }
}
