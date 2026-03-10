<?php

namespace App\Domains\HospitalVideo\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalVideo\Models\HospitalVideo;

final class HospitalVideoForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_VIDEO_REQUEST_SHOW);
    }

    public function view(AccountStaff $actor, HospitalVideo $video): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_VIDEO_REQUEST_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_VIDEO_REQUEST_UPDATE);
    }

    public function update(AccountStaff $actor, HospitalVideo $video): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_VIDEO_REQUEST_UPDATE);
    }

    public function delete(AccountStaff $actor, HospitalVideo $video): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_VIDEO_REQUEST_DELETE);
    }
}
