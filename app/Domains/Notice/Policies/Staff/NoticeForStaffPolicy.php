<?php

namespace App\Domains\Notice\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Notice\Models\Notice;

final class NoticeForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_NOTICE_SHOW);
    }

    public function view(AccountStaff $actor, Notice $notice): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_NOTICE_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_NOTICE_CREATE);
    }

    public function update(AccountStaff $actor, Notice $notice): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_NOTICE_UPDATE);
    }

    public function delete(AccountStaff $actor, Notice $notice): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_NOTICE_DELETE);
    }

    public function push(AccountStaff $actor, Notice $notice): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_NOTICE_PUSH);
    }
}
