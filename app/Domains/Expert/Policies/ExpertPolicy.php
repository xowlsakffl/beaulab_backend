<?php

namespace App\Domains\Expert\Policies;

use App\Domains\Expert\Models\Expert;
use App\Domains\Expert\Policies\Staff\ExpertForStaffPolicy;
use App\Domains\Partner\Models\AccountPartner;
use App\Domains\Staff\Models\AccountStaff;
use App\Domains\User\Models\AccountUser;

final class ExpertPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, Expert $expert): bool
    {
        return $this->delegate($actor)->view($actor, $expert);
    }

    public function create(mixed $actor): bool
    {
        return $this->delegate($actor)->create($actor);
    }

    public function update(mixed $actor, Expert $expert): bool
    {
        return $this->delegate($actor)->update($actor, $expert);
    }

    public function delete(mixed $actor, Expert $expert): bool
    {
        return $this->delegate($actor)->delete($actor, $expert);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(ExpertForStaffPolicy::class),
            $actor instanceof AccountPartner, $actor instanceof AccountUser => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, Expert $expert): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, Expert $expert): bool { return false; }
                public function delete(mixed $actor, Expert $expert): bool { return false; }
            },
            default => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, Expert $expert): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, Expert $expert): bool { return false; }
                public function delete(mixed $actor, Expert $expert): bool { return false; }
            },
        };
    }
}
