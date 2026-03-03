<?php

namespace App\Domains\Beauty\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Beauty\Models\Beauty;

final class BeautyForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_BEAUTY_SHOW);
    }

    public function view(AccountStaff $actor, Beauty $beauty): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_BEAUTY_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_BEAUTY_CREATE);
    }

    public function update(AccountStaff $actor, Beauty $beauty): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_BEAUTY_UPDATE);
    }

    public function delete(AccountStaff $actor, Beauty $beauty): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_BEAUTY_DELETE);
    }
}
