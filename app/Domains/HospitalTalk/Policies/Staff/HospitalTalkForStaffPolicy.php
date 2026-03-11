<?php

namespace App\Domains\HospitalTalk\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalTalk\Models\HospitalTalk;

final class HospitalTalkForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_SHOW);
    }

    public function view(AccountStaff $actor, HospitalTalk $talk): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_CREATE);
    }

    public function update(AccountStaff $actor, HospitalTalk $talk): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_UPDATE);
    }

    public function delete(AccountStaff $actor, HospitalTalk $talk): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_DELETE);
    }
}
