<?php

namespace App\Domains\Hospital\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\Hospital\Dto\Staff\HospitalForStaffDto;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Queries\Staff\HospitalListForStaffQuery;
use Illuminate\Support\Facades\Gate;

/**
 * HospitalListForStaffAction 역할 정의.
 * 병원 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HospitalListForStaffAction
{
    public function __construct(
        private readonly HospitalListForStaffQuery $query,
    ) {}

    /**
     * @param array{
     *   q?: string|null,
     *   start_date?: string|null,
     *   end_date?: string|null,
     *   status?: array<int, string>|null,
     *    allow_status?: array<int, string>|null,
     *   category_ids?: array<int, int|string>|null,
     *   include?: array<int, string>,
     *   sort?: string,
     *   direction?: 'asc'|'desc',
     *   per_page?: int
     * } $filters
     */
    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', Hospital::class);

        $paginator = $this->query->paginate($filters);

        return PaginatedResponse::fromPaginator(
            $paginator,
            fn (Hospital $hospital): array => HospitalForStaffDto::fromModel($hospital)->toArray(),
        );
    }
}
