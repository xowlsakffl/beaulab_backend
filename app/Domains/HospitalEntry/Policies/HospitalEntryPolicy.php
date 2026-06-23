<?php

namespace App\Domains\HospitalEntry\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalEntry\Models\HospitalEntry;
use App\Domains\HospitalEntry\Policies\Staff\HospitalEntryForStaffPolicy;

final class HospitalEntryPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, HospitalEntry $entry): bool
    {
        return $this->delegate($actor)->view($actor, $entry);
    }

    public function update(mixed $actor, HospitalEntry $entry): bool
    {
        return $this->delegate($actor)->update($actor, $entry);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(HospitalEntryForStaffPolicy::class),
            default => new class
            {
                public function viewAny(mixed $actor): bool
                {
                    return false;
                }

                public function view(mixed $actor, HospitalEntry $entry): bool
                {
                    return false;
                }

                public function update(mixed $actor, HospitalEntry $entry): bool
                {
                    return false;
                }
            },
        };
    }
}
