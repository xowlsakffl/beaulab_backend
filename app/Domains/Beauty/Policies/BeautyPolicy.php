<?php

namespace App\Domains\Beauty\Policies;

use App\Domains\Beauty\Models\Beauty;
use App\Domains\Beauty\Policies\Staff\BeautyForStaffPolicy;
use App\Domains\AccountStaff\Models\AccountStaff;


final class BeautyPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, Beauty $beauty): bool
    {
        return $this->delegate($actor)->view($actor, $beauty);
    }

    public function create(mixed $actor): bool
    {
        return $this->delegate($actor)->create($actor);
    }

    public function update(mixed $actor, Beauty $beauty): bool
    {
        return $this->delegate($actor)->update($actor, $beauty);
    }

    public function delete(mixed $actor, Beauty $beauty): bool
    {
        return $this->delegate($actor)->delete($actor, $beauty);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff   => app(BeautyForStaffPolicy::class),

            default => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, Beauty $beauty): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, Beauty $beauty): bool { return false; }
                public function delete(mixed $actor, Beauty $beauty): bool { return false; }
            },
        };
    }
}
