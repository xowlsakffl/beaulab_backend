<?php

namespace App\Domains\Hospital\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Staff\Models\AccountStaff;

final class HospitalForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_SHOW);
    }

    public function view(AccountStaff $actor, Hospital $hospital): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_CREATE);
    }

    public function update(AccountStaff $actor, Hospital $hospital): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_UPDATE);
    }

    public function delete(AccountStaff $actor, Hospital $hospital): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_DELETE);
    }
}
