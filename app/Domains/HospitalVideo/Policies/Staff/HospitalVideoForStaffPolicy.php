<?php

namespace App\Domains\HospitalVideo\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalVideo\Models\HospitalVideo;

final class HospitalVideoForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_VIDEO_SHOW);
    }

    public function view(AccountStaff $actor, HospitalVideo $video): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_VIDEO_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_VIDEO_CREATE);
    }

    public function update(AccountStaff $actor, HospitalVideo $video): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_VIDEO_UPDATE);
    }

    public function delete(AccountStaff $actor, HospitalVideo $video): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_VIDEO_DELETE);
    }

    public function cancel(AccountStaff $actor, HospitalVideo $video): bool
    {
        return false;
    }
}
