<?php

namespace App\Domains\AccountStaff\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;

final class AccountStaffForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_STAFF_SHOW);
    }

    public function view(AccountStaff $actor, AccountStaff $staff): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_STAFF_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_STAFF_CREATE);
    }

    public function update(AccountStaff $actor, AccountStaff $staff): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_STAFF_UPDATE);
    }

    public function delete(AccountStaff $actor, AccountStaff $staff): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_STAFF_DELETE);
    }

    public function viewProfile(AccountStaff $actor, AccountStaff $staff): bool
    {
        return $actor->is($staff) && $actor->can(AccessPermissions::COMMON_PROFILE_SHOW);
    }

    public function updateProfile(AccountStaff $actor, AccountStaff $staff): bool
    {
        return $actor->is($staff) && $actor->can(AccessPermissions::COMMON_PROFILE_UPDATE);
    }
}
