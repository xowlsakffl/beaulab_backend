<?php

namespace App\Domains\HospitalEvent\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use App\Domains\HospitalEvent\Policies\Staff\HospitalEventRealModelDBForStaffPolicy;

final class HospitalEventRealModelDBPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, HospitalEventRealModelDB $application): bool
    {
        return $this->delegate($actor)->view($actor, $application);
    }

    public function update(mixed $actor, HospitalEventRealModelDB $application): bool
    {
        return $this->delegate($actor)->update($actor, $application);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(HospitalEventRealModelDBForStaffPolicy::class),
            default => new class
            {
                public function viewAny(mixed $actor): bool
                {
                    return false;
                }

                public function view(mixed $actor, HospitalEventRealModelDB $application): bool
                {
                    return false;
                }

                public function update(mixed $actor, HospitalEventRealModelDB $application): bool
                {
                    return false;
                }
            },
        };
    }
}
