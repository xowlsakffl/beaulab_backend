<?php

namespace App\Domains\HospitalTalk\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalTalk\Models\HospitalTalkComment;

final class HospitalTalkCommentForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_COMMENT_SHOW);
    }

    public function view(AccountStaff $actor, HospitalTalkComment $comment): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_COMMENT_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_COMMENT_CREATE);
    }

    public function update(AccountStaff $actor, HospitalTalkComment $comment): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_COMMENT_UPDATE);
    }

    public function delete(AccountStaff $actor, HospitalTalkComment $comment): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_TALK_COMMENT_DELETE);
    }
}
