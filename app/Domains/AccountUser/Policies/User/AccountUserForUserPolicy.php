<?php

declare(strict_types=1);

namespace App\Domains\User\Policies\User;

use App\Domains\User\Models\AccountUser;

final class AccountUserForUserPolicy
{
    public function viewAny(AccountUser $actor): bool
    {
        return false;
    }

    public function view(AccountUser $actor, AccountUser $user): bool
    {
        return $actor->id === $user->id;
    }

    public function update(AccountUser $actor, AccountUser $user): bool
    {
        return $actor->id === $user->id;
    }

    public function delete(AccountUser $actor, AccountUser $user): bool
    {
        return $actor->id === $user->id;
    }
}
