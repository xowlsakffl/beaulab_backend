<?php

namespace App\Domains\HospitalEvent\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalEvent\Models\HospitalEventDB;
use App\Domains\HospitalEvent\Policies\Staff\HospitalEventDBForStaffPolicy;

final class HospitalEventDBPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, HospitalEventDB $eventDB): bool
    {
        return $this->delegate($actor)->view($actor, $eventDB);
    }

    public function update(mixed $actor, HospitalEventDB $eventDB): bool
    {
        return $this->delegate($actor)->update($actor, $eventDB);
    }

    public function updateStatus(mixed $actor, ?HospitalEventDB $eventDB = null): bool
    {
        return $actor instanceof AccountStaff
            && app(HospitalEventDBForStaffPolicy::class)->updateStatus($actor, $eventDB);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(HospitalEventDBForStaffPolicy::class),
            default => new class
            {
                public function viewAny(mixed $actor): bool
                {
                    return false;
                }

                public function view(mixed $actor, HospitalEventDB $eventDB): bool
                {
                    return false;
                }

                public function update(mixed $actor, HospitalEventDB $eventDB): bool
                {
                    return false;
                }
            },
        };
    }
}
