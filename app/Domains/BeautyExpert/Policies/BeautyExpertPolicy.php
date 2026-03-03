<?php

namespace App\Domains\BeautyExpert\Policies;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\BeautyExpert\Models\BeautyExpert;
use App\Domains\BeautyExpert\Policies\Staff\BeautyExpertForStaffPolicy;
use App\Domains\Staff\Models\AccountStaff;

final class BeautyExpertPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, BeautyExpert $expert): bool
    {
        return $this->delegate($actor)->view($actor, $expert);
    }

    public function create(mixed $actor): bool
    {
        return $this->delegate($actor)->create($actor);
    }

    public function update(mixed $actor, BeautyExpert $expert): bool
    {
        return $this->delegate($actor)->update($actor, $expert);
    }

    public function delete(mixed $actor, BeautyExpert $expert): bool
    {
        return $this->delegate($actor)->delete($actor, $expert);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(BeautyExpertForStaffPolicy::class),
            //$actor instanceof AccountPartner,
            $actor instanceof AccountUser => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, BeautyExpert $expert): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, BeautyExpert $expert): bool { return false; }
                public function delete(mixed $actor, BeautyExpert $expert): bool { return false; }
            },
            default => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, BeautyExpert $expert): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, BeautyExpert $expert): bool { return false; }
                public function delete(mixed $actor, BeautyExpert $expert): bool { return false; }
            },
        };
    }
}
