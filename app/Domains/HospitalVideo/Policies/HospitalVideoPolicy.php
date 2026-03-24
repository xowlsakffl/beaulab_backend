<?php

namespace App\Domains\HospitalVideo\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Domains\HospitalVideo\Policies\Hospital\HospitalVideoForHospitalPolicy;
use App\Domains\HospitalVideo\Policies\Staff\HospitalVideoForStaffPolicy;

final class HospitalVideoPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, HospitalVideo $video): bool
    {
        return $this->delegate($actor)->view($actor, $video);
    }

    public function create(mixed $actor): bool
    {
        return $this->delegate($actor)->create($actor);
    }

    public function update(mixed $actor, HospitalVideo $video): bool
    {
        return $this->delegate($actor)->update($actor, $video);
    }

    public function delete(mixed $actor, HospitalVideo $video): bool
    {
        return $this->delegate($actor)->delete($actor, $video);
    }

    public function cancel(mixed $actor, HospitalVideo $video): bool
    {
        return $this->delegate($actor)->cancel($actor, $video);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(HospitalVideoForStaffPolicy::class),
            $actor instanceof AccountHospital => app(HospitalVideoForHospitalPolicy::class),
            $actor instanceof AccountUser => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, HospitalVideo $video): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, HospitalVideo $video): bool { return false; }
                public function delete(mixed $actor, HospitalVideo $video): bool { return false; }
                public function cancel(mixed $actor, HospitalVideo $video): bool { return false; }
            },
            default => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, HospitalVideo $video): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, HospitalVideo $video): bool { return false; }
                public function delete(mixed $actor, HospitalVideo $video): bool { return false; }
                public function cancel(mixed $actor, HospitalVideo $video): bool { return false; }
            },
        };
    }
}
