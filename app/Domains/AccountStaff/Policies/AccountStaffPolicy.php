<?php

namespace App\Domains\AccountStaff\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountStaff\Policies\Staff\AccountStaffForStaffPolicy;

final class AccountStaffPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, AccountStaff $staff): bool
    {
        return $this->delegate($actor)->view($actor, $staff);
    }

    public function create(mixed $actor): bool
    {
        return $this->delegate($actor)->create($actor);
    }

    public function update(mixed $actor, AccountStaff $staff): bool
    {
        return $this->delegate($actor)->update($actor, $staff);
    }

    public function delete(mixed $actor, AccountStaff $staff): bool
    {
        return $this->delegate($actor)->delete($actor, $staff);
    }

    public function viewProfile(mixed $actor, AccountStaff $staff): bool
    {
        return $this->delegate($actor)->viewProfile($actor, $staff);
    }

    public function updateProfile(mixed $actor, AccountStaff $staff): bool
    {
        return $this->delegate($actor)->updateProfile($actor, $staff);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(AccountStaffForStaffPolicy::class),
            default => new class
            {
                public function viewAny(mixed $actor): bool
                {
                    return false;
                }

                public function view(mixed $actor, AccountStaff $staff): bool
                {
                    return false;
                }

                public function create(mixed $actor): bool
                {
                    return false;
                }

                public function update(mixed $actor, AccountStaff $staff): bool
                {
                    return false;
                }

                public function delete(mixed $actor, AccountStaff $staff): bool
                {
                    return false;
                }

                public function viewProfile(mixed $actor, AccountStaff $staff): bool
                {
                    return false;
                }

                public function updateProfile(mixed $actor, AccountStaff $staff): bool
                {
                    return false;
                }
            },
        };
    }
}
