<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;

/**
 * AccountUserForStaffPolicy 역할 정의.
 * 일반 회원 계정 도메인의 권한 정책으로, 현재 actor가 이 리소스에 수행할 수 있는 작업인지 판단하는 권한 규칙을 정의한다.
 */
final class AccountUserForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_USER_SHOW);
    }

    public function view(AccountStaff $actor, AccountUser $user): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_USER_SHOW);
    }

    public function update(AccountStaff $actor, AccountUser $user): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_USER_UPDATE);
    }

    public function delete(AccountStaff $actor, AccountUser $user): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_USER_DELETE);
    }
}
