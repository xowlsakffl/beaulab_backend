<?php

namespace App\Domains\Doctor\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\Doctor\Models\Doctor;
use App\Domains\Staff\Models\AccountStaff;

final class DoctorForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_DOCTOR_SHOW);
    }

    public function view(AccountStaff $actor, Doctor $doctor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_DOCTOR_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_DOCTOR_CREATE);
    }

    public function update(AccountStaff $actor, Doctor $doctor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_DOCTOR_UPDATE);
    }

    public function delete(AccountStaff $actor, Doctor $doctor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_DOCTOR_DELETE);
    }
}
