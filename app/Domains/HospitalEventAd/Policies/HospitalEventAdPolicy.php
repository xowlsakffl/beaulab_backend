<?php

namespace App\Domains\HospitalEventAd\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use App\Domains\HospitalEventAd\Policies\Staff\HospitalEventAdForStaffPolicy;

final class HospitalEventAdPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, HospitalEventAd $ad): bool
    {
        return $this->delegate($actor)->view($actor, $ad);
    }

    public function create(mixed $actor): bool
    {
        return $this->delegate($actor)->create($actor);
    }

    public function update(mixed $actor, HospitalEventAd $ad): bool
    {
        return $this->delegate($actor)->update($actor, $ad);
    }

    public function updateStatus(mixed $actor, ?HospitalEventAd $ad = null): bool
    {
        return $actor instanceof AccountStaff
            && app(HospitalEventAdForStaffPolicy::class)->updateStatus($actor, $ad);
    }

    public function delete(mixed $actor, HospitalEventAd $ad): bool
    {
        return $this->delegate($actor)->delete($actor, $ad);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(HospitalEventAdForStaffPolicy::class),
            default => new class
            {
                public function viewAny(mixed $actor): bool
                {
                    return false;
                }

                public function view(mixed $actor, HospitalEventAd $ad): bool
                {
                    return false;
                }

                public function create(mixed $actor): bool
                {
                    return false;
                }

                public function update(mixed $actor, HospitalEventAd $ad): bool
                {
                    return false;
                }

                public function delete(mixed $actor, HospitalEventAd $ad): bool
                {
                    return false;
                }
            },
        };
    }
}
