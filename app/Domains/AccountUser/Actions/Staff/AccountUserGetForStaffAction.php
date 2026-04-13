<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Actions\Staff;

use App\Domains\AccountUser\Dto\Staff\AccountUserForStaffDetailDto;
use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Support\Facades\Gate;

/**
 * AccountUserGetForStaffAction 역할 정의.
 * 일반 회원 계정 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class AccountUserGetForStaffAction
{
    public function execute(AccountUser $user): array
    {
        Gate::authorize('view', $user);

        return [
            'user' => AccountUserForStaffDetailDto::fromModel($user)->toArray(),
        ];
    }
}
