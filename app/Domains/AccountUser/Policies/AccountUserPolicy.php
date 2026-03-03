<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Policies;

use App\Domains\Staff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Policies\Staff\AccountUserForStaffPolicy;
use App\Domains\AccountUser\Policies\User\AccountUserForUserPolicy;

final class AccountUserPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, AccountUser $user): bool
    {
        return $this->delegate($actor)->view($actor, $user);
    }

    public function update(mixed $actor, AccountUser $user): bool
    {
        return $this->delegate($actor)->update($actor, $user);
    }

    public function delete(mixed $actor, AccountUser $user): bool
    {
        return $this->delegate($actor)->delete($actor, $user);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(AccountUserForStaffPolicy::class),
            //$actor instanceof AccountPartner => app(AccountUserForPartnerPolicy::class),
            $actor instanceof AccountUser => app(AccountUserForUserPolicy::class),
            default => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, AccountUser $user): bool { return false; }
                public function update(mixed $actor, AccountUser $user): bool { return false; }
                public function delete(mixed $actor, AccountUser $user): bool { return false; }
            },
        };
    }
}
