<?php

namespace App\Domains\BeautyExpert\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\BeautyExpert\Models\BeautyExpert;
use App\Domains\AccountStaff\Models\AccountStaff;

final class BeautyExpertForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_EXPERT_SHOW);
    }

    public function view(AccountStaff $actor, BeautyExpert $expert): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_EXPERT_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_EXPERT_CREATE);
    }

    public function update(AccountStaff $actor, BeautyExpert $expert): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_EXPERT_UPDATE);
    }

    public function delete(AccountStaff $actor, BeautyExpert $expert): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_EXPERT_DELETE);
    }
}
