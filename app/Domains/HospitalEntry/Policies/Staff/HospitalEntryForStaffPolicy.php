<?php

namespace App\Domains\HospitalEntry\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalEntry\Models\HospitalEntry;

final class HospitalEntryForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_ENTRY_SHOW);
    }

    public function view(AccountStaff $actor, HospitalEntry $entry): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_ENTRY_SHOW);
    }

    public function update(AccountStaff $actor, HospitalEntry $entry): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_ENTRY_UPDATE);
    }

    public function updateStatus(AccountStaff $actor, ?HospitalEntry $entry = null): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_ENTRY_STATUS_UPDATE);
    }
}
