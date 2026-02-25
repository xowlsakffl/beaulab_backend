<?php

namespace App\Domains\Expert\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\Expert\Models\Expert;
use App\Domains\Staff\Models\AccountStaff;

final class ExpertForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_EXPERT_SHOW);
    }

    public function view(AccountStaff $actor, Expert $expert): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_EXPERT_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_EXPERT_CREATE);
    }

    public function update(AccountStaff $actor, Expert $expert): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_EXPERT_UPDATE);
    }

    public function delete(AccountStaff $actor, Expert $expert): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_EXPERT_DELETE);
    }
}
