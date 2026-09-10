<?php

namespace App\Domains\HospitalEvent\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalEvent\Models\HospitalEvent;

final class HospitalEventForStaffPolicy
{
    public function preview(AccountStaff $actor): bool
    {
        return $actor->canAny([
            AccessPermissions::BEAULAB_HOSPITAL_EVENT_SHOW,
            AccessPermissions::BEAULAB_HOSPITAL_EVENT_CREATE,
            AccessPermissions::BEAULAB_HOSPITAL_EVENT_UPDATE,
        ]);
    }

    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_EVENT_SHOW);
    }

    public function view(AccountStaff $actor, HospitalEvent $event): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_EVENT_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_EVENT_CREATE);
    }

    public function update(AccountStaff $actor, HospitalEvent $event): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_EVENT_UPDATE);
    }

    public function updateStatus(AccountStaff $actor, ?HospitalEvent $event = null): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_EVENT_STATUS_UPDATE);
    }

    public function delete(AccountStaff $actor, HospitalEvent $event): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_EVENT_DELETE);
    }
}
