<?php

namespace App\Domains\HospitalEventAd\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;

final class HospitalEventAdForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_EVENT_AD_SHOW);
    }

    public function view(AccountStaff $actor, HospitalEventAd $ad): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_EVENT_AD_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_EVENT_AD_CREATE);
    }

    public function update(AccountStaff $actor, HospitalEventAd $ad): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_EVENT_AD_UPDATE);
    }

    public function delete(AccountStaff $actor, HospitalEventAd $ad): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_EVENT_AD_DELETE);
    }
}
