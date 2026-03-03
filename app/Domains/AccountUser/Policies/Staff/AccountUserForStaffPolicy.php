<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;

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
