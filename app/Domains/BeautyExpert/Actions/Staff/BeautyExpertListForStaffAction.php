<?php

namespace App\Domains\BeautyExpert\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\BeautyExpert\Dto\Staff\BeautyExpertForStaffDto;
use App\Domains\BeautyExpert\Models\BeautyExpert;
use App\Domains\BeautyExpert\Queries\Staff\BeautyExpertListForStaffQuery;
use Illuminate\Support\Facades\Gate;

/**
 * BeautyExpertListForStaffAction 역할 정의.
 * 뷰티 전문가 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class BeautyExpertListForStaffAction
{
    public function __construct(private readonly BeautyExpertListForStaffQuery $query) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', BeautyExpert::class);

        $paginator = $this->query->paginate($filters);

        return PaginatedResponse::fromPaginator(
            $paginator,
            fn ($expert): array => BeautyExpertForStaffDto::fromModel($expert)->toArray(),
        );
    }
}
