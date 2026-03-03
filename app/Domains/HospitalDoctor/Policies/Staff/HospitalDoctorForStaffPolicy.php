<?php

namespace App\Domains\HospitalDoctor\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\Staff\Models\AccountStaff;

final class HospitalDoctorForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_DOCTOR_SHOW);
    }

    public function view(AccountStaff $actor, HospitalDoctor $doctor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_DOCTOR_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_DOCTOR_CREATE);
    }

    public function update(AccountStaff $actor, HospitalDoctor $doctor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_DOCTOR_UPDATE);
    }

    public function delete(AccountStaff $actor, HospitalDoctor $doctor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_DOCTOR_DELETE);
    }
}
