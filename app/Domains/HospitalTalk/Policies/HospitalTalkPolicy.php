<?php

namespace App\Domains\HospitalTalk\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\HospitalTalk\Models\HospitalTalk;
use App\Domains\HospitalTalk\Policies\Staff\HospitalTalkForStaffPolicy;

final class HospitalTalkPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, HospitalTalk $talk): bool
    {
        return $this->delegate($actor)->view($actor, $talk);
    }

    public function create(mixed $actor): bool
    {
        return $this->delegate($actor)->create($actor);
    }

    public function update(mixed $actor, HospitalTalk $talk): bool
    {
        return $this->delegate($actor)->update($actor, $talk);
    }

    public function delete(mixed $actor, HospitalTalk $talk): bool
    {
        return $this->delegate($actor)->delete($actor, $talk);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(HospitalTalkForStaffPolicy::class),
            $actor instanceof AccountUser => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, HospitalTalk $talk): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, HospitalTalk $talk): bool { return false; }
                public function delete(mixed $actor, HospitalTalk $talk): bool { return false; }
            },
            default => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, HospitalTalk $talk): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, HospitalTalk $talk): bool { return false; }
                public function delete(mixed $actor, HospitalTalk $talk): bool { return false; }
            },
        };
    }
}
