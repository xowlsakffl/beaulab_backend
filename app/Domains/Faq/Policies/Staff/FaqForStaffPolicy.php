<?php

namespace App\Domains\Faq\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Faq\Models\Faq;

final class FaqForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_FAQ_SHOW);
    }

    public function view(AccountStaff $actor, Faq $faq): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_FAQ_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_FAQ_CREATE);
    }

    public function update(AccountStaff $actor, Faq $faq): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_FAQ_UPDATE);
    }

    public function delete(AccountStaff $actor, Faq $faq): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_FAQ_DELETE);
    }
}
