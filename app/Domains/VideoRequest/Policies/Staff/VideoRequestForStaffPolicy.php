<?php

namespace App\Domains\VideoRequest\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\Staff\Models\AccountStaff;
use App\Domains\VideoRequest\Models\VideoRequest;

final class VideoRequestForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_VIDEO_REQUEST_SHOW);
    }

    public function view(AccountStaff $actor, VideoRequest $videoRequest): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_VIDEO_REQUEST_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return false;
    }

    public function update(AccountStaff $actor, VideoRequest $videoRequest): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_VIDEO_REQUEST_UPDATE);
    }

    public function delete(AccountStaff $actor, VideoRequest $videoRequest): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_VIDEO_REQUEST_DELETE);
    }
}
