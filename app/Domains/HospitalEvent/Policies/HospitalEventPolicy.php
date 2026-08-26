<?php

namespace App\Domains\HospitalEvent\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Policies\Staff\HospitalEventForStaffPolicy;

final class HospitalEventPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, HospitalEvent $event): bool
    {
        return $this->delegate($actor)->view($actor, $event);
    }

    public function create(mixed $actor): bool
    {
        return $this->delegate($actor)->create($actor);
    }

    public function update(mixed $actor, HospitalEvent $event): bool
    {
        return $this->delegate($actor)->update($actor, $event);
    }

    public function updateStatus(mixed $actor, ?HospitalEvent $event = null): bool
    {
        return $actor instanceof AccountStaff
            && app(HospitalEventForStaffPolicy::class)->updateStatus($actor, $event);
    }

    public function delete(mixed $actor, HospitalEvent $event): bool
    {
        return $this->delegate($actor)->delete($actor, $event);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(HospitalEventForStaffPolicy::class),
            $actor instanceof AccountUser => new class
            {
                public function viewAny(mixed $actor): bool
                {
                    return false;
                }

                public function view(mixed $actor, HospitalEvent $event): bool
                {
                    return false;
                }

                public function create(mixed $actor): bool
                {
                    return false;
                }

                public function update(mixed $actor, HospitalEvent $event): bool
                {
                    return false;
                }

                public function delete(mixed $actor, HospitalEvent $event): bool
                {
                    return false;
                }
            },
            default => new class
            {
                public function viewAny(mixed $actor): bool
                {
                    return false;
                }

                public function view(mixed $actor, HospitalEvent $event): bool
                {
                    return false;
                }

                public function create(mixed $actor): bool
                {
                    return false;
                }

                public function update(mixed $actor, HospitalEvent $event): bool
                {
                    return false;
                }

                public function delete(mixed $actor, HospitalEvent $event): bool
                {
                    return false;
                }
            },
        };
    }
}
