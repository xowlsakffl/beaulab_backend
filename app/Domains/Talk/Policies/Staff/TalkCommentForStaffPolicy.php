<?php

namespace App\Domains\Talk\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Talk\Models\TalkComment;

final class TalkCommentForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_SHOW);
    }

    public function view(AccountStaff $actor, TalkComment $comment): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_CREATE);
    }

    public function update(AccountStaff $actor, TalkComment $comment): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_UPDATE);
    }

    public function delete(AccountStaff $actor, TalkComment $comment): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_DELETE);
    }
}
