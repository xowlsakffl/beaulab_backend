<?php

namespace App\Domains\Common\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Models\Hashtag\Hashtag;

final class HashtagForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HASHTAG_MANAGE);
    }

    public function view(AccountStaff $actor, Hashtag $hashtag): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HASHTAG_MANAGE);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HASHTAG_MANAGE);
    }

    public function update(AccountStaff $actor, Hashtag $hashtag): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HASHTAG_MANAGE);
    }

    public function delete(AccountStaff $actor, Hashtag $hashtag): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HASHTAG_MANAGE);
    }
}
