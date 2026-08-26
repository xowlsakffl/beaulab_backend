<?php

namespace App\Domains\HospitalEvent\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;

final class HospitalEventRealModelDBForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_EVENT_REAL_MODEL_DB_SHOW);
    }

    public function view(AccountStaff $actor, HospitalEventRealModelDB $application): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_EVENT_REAL_MODEL_DB_SHOW);
    }

    public function update(AccountStaff $actor, HospitalEventRealModelDB $application): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_EVENT_REAL_MODEL_DB_UPDATE);
    }

    public function updateStatus(AccountStaff $actor, ?HospitalEventRealModelDB $application = null): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_EVENT_REAL_MODEL_DB_STATUS_UPDATE);
    }
}
