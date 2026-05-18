<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\AccountUser\Dto\Staff\AccountUserForStaffDto;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Queries\Staff\AccountUserListForStaffQuery;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * AccountUserListForStaffAction 역할 정의.
 * 일반 회원 계정 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class AccountUserListForStaffAction
{
    public function __construct(
        private readonly AccountUserListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', AccountUser::class);

        Log::info('일반회원 목록 조회 실행', ['filters' => $filters]);

        $paginator = $this->query->paginate($filters);

        return PaginatedResponse::fromPaginator(
            $paginator,
            fn (AccountUser $user): array => AccountUserForStaffDto::fromModel($user)->toArray(),
        );
    }
}
