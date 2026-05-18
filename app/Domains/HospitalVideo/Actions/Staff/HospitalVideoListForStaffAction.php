<?php

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\HospitalVideo\Dto\Staff\HospitalVideoForStaffDto;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Domains\HospitalVideo\Queries\Staff\HospitalVideoListForStaffQuery;
use Illuminate\Support\Facades\Gate;

/**
 * HospitalVideoListForStaffAction 역할 정의.
 * 병원 동영상 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HospitalVideoListForStaffAction
{
    public function __construct(private readonly HospitalVideoListForStaffQuery $query) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalVideo::class);

        $paginator = $this->query->paginate($filters);

        return PaginatedResponse::fromPaginator(
            $paginator,
            fn ($video): array => HospitalVideoForStaffDto::fromModel($video)->toArray(),
        );
    }
}
