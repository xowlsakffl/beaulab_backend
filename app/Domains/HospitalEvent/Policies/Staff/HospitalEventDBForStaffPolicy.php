<?php

namespace App\Domains\HospitalEvent\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalEvent\Models\HospitalEventDB;

final class HospitalEventDBForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_EVENT_DB_SHOW);
    }

    public function view(AccountStaff $actor, HospitalEventDB $eventDB): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_EVENT_DB_SHOW);
    }

    public function update(AccountStaff $actor, HospitalEventDB $eventDB): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_EVENT_DB_UPDATE);
    }

    public function updateStatus(AccountStaff $actor, ?HospitalEventDB $eventDB = null): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_EVENT_DB_STATUS_UPDATE);
    }
}
