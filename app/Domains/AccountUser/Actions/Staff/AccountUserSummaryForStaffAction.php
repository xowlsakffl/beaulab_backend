<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Actions\Staff;

use App\Domains\AccountUser\Dto\Staff\AccountUserSummaryForStaffDto;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Queries\Staff\AccountUserSummaryForStaffQuery;
use Illuminate\Support\Facades\Gate;

/**
 * AccountUserSummaryForStaffAction 역할 정의.
 * 일반회원 목록 상단 통계 조회 유스케이스를 담당한다.
 */
final class AccountUserSummaryForStaffAction
{
    public function __construct(
        private readonly AccountUserSummaryForStaffQuery $query,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        Gate::authorize('viewAny', AccountUser::class);

        return AccountUserSummaryForStaffDto::fromArray($this->query->get())->toArray();
    }
}
