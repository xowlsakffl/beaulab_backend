<?php

namespace App\Domains\Talk\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Policies\Staff\TalkForStaffPolicy;

final class TalkPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, Talk $talk): bool
    {
        return $this->delegate($actor)->view($actor, $talk);
    }

    public function create(mixed $actor): bool
    {
        return $this->delegate($actor)->create($actor);
    }

    public function update(mixed $actor, Talk $talk): bool
    {
        return $this->delegate($actor)->update($actor, $talk);
    }

    public function delete(mixed $actor, Talk $talk): bool
    {
        return $this->delegate($actor)->delete($actor, $talk);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(TalkForStaffPolicy::class),
            $actor instanceof AccountUser => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, Talk $talk): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, Talk $talk): bool { return false; }
                public function delete(mixed $actor, Talk $talk): bool { return false; }
            },
            default => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, Talk $talk): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, Talk $talk): bool { return false; }
                public function delete(mixed $actor, Talk $talk): bool { return false; }
            },
        };
    }
}
