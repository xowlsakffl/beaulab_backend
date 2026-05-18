<?php

namespace App\Domains\Notice\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\Notice\Dto\Staff\NoticeForStaffDto;
use App\Domains\Notice\Models\Notice;
use App\Domains\Notice\Queries\Staff\NoticeListForStaffQuery;
use Illuminate\Support\Facades\Gate;

/**
 * NoticeListForStaffAction 역할 정의.
 * 공지사항 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class NoticeListForStaffAction
{
    public function __construct(
        private readonly NoticeListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', Notice::class);

        $paginator = $this->query->paginate($filters);

        return PaginatedResponse::fromPaginator(
            $paginator,
            static fn (Notice $notice): array => NoticeForStaffDto::fromModel($notice)->toArray(),
        );
    }
}
