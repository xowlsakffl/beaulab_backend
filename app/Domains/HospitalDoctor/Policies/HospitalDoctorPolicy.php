<?php

namespace App\Domains\HospitalDoctor\Policies;

use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalDoctor\Policies\Staff\HospitalDoctorForStaffPolicy;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;

final class HospitalDoctorPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, HospitalDoctor $doctor): bool
    {
        return $this->delegate($actor)->view($actor, $doctor);
    }

    public function create(mixed $actor): bool
    {
        return $this->delegate($actor)->create($actor);
    }

    public function update(mixed $actor, HospitalDoctor $doctor): bool
    {
        return $this->delegate($actor)->update($actor, $doctor);
    }

    public function delete(mixed $actor, HospitalDoctor $doctor): bool
    {
        return $this->delegate($actor)->delete($actor, $doctor);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(HospitalDoctorForStaffPolicy::class),
            //$actor instanceof AccountPartner,
            $actor instanceof AccountUser => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, HospitalDoctor $doctor): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, HospitalDoctor $doctor): bool { return false; }
                public function delete(mixed $actor, HospitalDoctor $doctor): bool { return false; }
            },
            default => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, HospitalDoctor $doctor): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, HospitalDoctor $doctor): bool { return false; }
                public function delete(mixed $actor, HospitalDoctor $doctor): bool { return false; }
            },
        };
    }
}
