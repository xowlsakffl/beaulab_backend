<?php

namespace App\Domains\Doctor\Policies\Staff;

use App\Domains\Doctor\Models\Doctor;
use App\Domains\Staff\Models\AccountStaff;

final class DoctorForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can('beaulab.doctor.show');
    }

    public function view(AccountStaff $actor, Doctor $doctor): bool
    {
        return $actor->can('beaulab.doctor.show');
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can('beaulab.doctor.create');
    }

    public function update(AccountStaff $actor, Doctor $doctor): bool
    {
        return $actor->can('beaulab.doctor.update');
    }

    public function delete(AccountStaff $actor, Doctor $doctor): bool
    {
        return $actor->can('beaulab.doctor.delete');
    }
}
