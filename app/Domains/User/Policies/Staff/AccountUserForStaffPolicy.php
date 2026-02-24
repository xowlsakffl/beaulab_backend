<?php

declare(strict_types=1);

namespace App\Domains\User\Policies\Staff;

use App\Domains\Staff\Models\AccountStaff;
use App\Domains\User\Models\AccountUser;

final class AccountUserForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can('beaulab.user.show');
    }

    public function view(AccountStaff $actor, AccountUser $user): bool
    {
        return $actor->can('beaulab.user.show');
    }

    public function update(AccountStaff $actor, AccountUser $user): bool
    {
        return $actor->can('beaulab.user.update');
    }

    public function delete(AccountStaff $actor, AccountUser $user): bool
    {
        return $actor->can('beaulab.user.delete');
    }
}
