<?php

namespace App\Domains\Talk\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\Talk\Dto\Staff\TalkForStaffDto;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Queries\Staff\TalkListForStaffQuery;
use Illuminate\Support\Facades\Gate;

/**
 * TalkListForStaffAction 역할 정의.
 * 토크 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class TalkListForStaffAction
{
    public function __construct(
        private readonly TalkListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', Talk::class);

        $paginator = $this->query->paginate($filters);

        return PaginatedResponse::fromPaginator(
            $paginator,
            fn ($talk): array => TalkForStaffDto::fromModel($talk)->toArray(),
        );
    }
}
