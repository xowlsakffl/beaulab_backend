<?php

namespace App\Domains\Notice\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Notice\Models\Notice;
use App\Domains\Notice\Policies\Staff\NoticeForStaffPolicy;

final class NoticePolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, Notice $notice): bool
    {
        return $this->delegate($actor)->view($actor, $notice);
    }

    public function create(mixed $actor): bool
    {
        return $this->delegate($actor)->create($actor);
    }

    public function update(mixed $actor, Notice $notice): bool
    {
        return $this->delegate($actor)->update($actor, $notice);
    }

    public function delete(mixed $actor, Notice $notice): bool
    {
        return $this->delegate($actor)->delete($actor, $notice);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(NoticeForStaffPolicy::class),
            default => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, Notice $notice): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, Notice $notice): bool { return false; }
                public function delete(mixed $actor, Notice $notice): bool { return false; }
            },
        };
    }
}
