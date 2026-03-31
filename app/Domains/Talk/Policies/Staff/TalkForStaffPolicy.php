<?php

namespace App\Domains\Talk\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Talk\Models\Talk;

final class TalkForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_SHOW);
    }

    public function view(AccountStaff $actor, Talk $talk): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_CREATE);
    }

    public function update(AccountStaff $actor, Talk $talk): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_UPDATE);
    }

    public function delete(AccountStaff $actor, Talk $talk): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_DELETE);
    }
}
