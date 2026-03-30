<?php

namespace App\Domains\Hospital\Policies;

use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Policies\Hospital\HospitalForHospitalPolicy;
use App\Domains\Hospital\Policies\Staff\HospitalForStaffPolicy;

final class HospitalPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, Hospital $hospital): bool
    {
        return $this->delegate($actor)->view($actor, $hospital);
    }

    public function create(mixed $actor): bool
    {
        return $this->delegate($actor)->create($actor);
    }

    public function update(mixed $actor, Hospital $hospital): bool
    {
        return $this->delegate($actor)->update($actor, $hospital);
    }

    public function delete(mixed $actor, Hospital $hospital): bool
    {
        return $this->delegate($actor)->delete($actor, $hospital);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff   => app(HospitalForStaffPolicy::class),
            $actor instanceof AccountHospital => app(HospitalForHospitalPolicy::class),
            //$actor instanceof AccountUser    => app(HospitalForUserPolicy::class),
            default => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, Hospital $hospital): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, Hospital $hospital): bool { return false; }
                public function delete(mixed $actor, Hospital $hospital): bool { return false; }
            },
        };
    }
}
