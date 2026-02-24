<?php

namespace App\Domains\Doctor\Policies;

use App\Domains\Doctor\Models\Doctor;
use App\Domains\Doctor\Policies\Staff\DoctorForStaffPolicy;
use App\Domains\Partner\Models\AccountPartner;
use App\Domains\Staff\Models\AccountStaff;
use App\Domains\User\Models\AccountUser;

final class DoctorPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, Doctor $doctor): bool
    {
        return $this->delegate($actor)->view($actor, $doctor);
    }

    public function create(mixed $actor): bool
    {
        return $this->delegate($actor)->create($actor);
    }

    public function update(mixed $actor, Doctor $doctor): bool
    {
        return $this->delegate($actor)->update($actor, $doctor);
    }

    public function delete(mixed $actor, Doctor $doctor): bool
    {
        return $this->delegate($actor)->delete($actor, $doctor);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(DoctorForStaffPolicy::class),
            $actor instanceof AccountPartner, $actor instanceof AccountUser => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, Doctor $doctor): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, Doctor $doctor): bool { return false; }
                public function delete(mixed $actor, Doctor $doctor): bool { return false; }
            },
            default => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, Doctor $doctor): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, Doctor $doctor): bool { return false; }
                public function delete(mixed $actor, Doctor $doctor): bool { return false; }
            },
        };
    }
}
