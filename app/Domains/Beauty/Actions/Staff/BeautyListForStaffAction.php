<?php

namespace App\Domains\Beauty\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\Beauty\Dto\Staff\BeautyForStaffDto;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\Beauty\Queries\Staff\BeautyListForStaffQuery;
use Illuminate\Support\Facades\Gate;

/**
 * BeautyListForStaffAction 역할 정의.
 * 뷰티 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class BeautyListForStaffAction
{
    public function __construct(
        private readonly BeautyListForStaffQuery $query,
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
        Gate::authorize('viewAny', Beauty::class);

        $paginator = $this->query->paginate($filters);

        return PaginatedResponse::fromPaginator(
            $paginator,
            fn (Beauty $beauty): array => BeautyForStaffDto::fromModel($beauty)->toArray(),
        );
    }
}
