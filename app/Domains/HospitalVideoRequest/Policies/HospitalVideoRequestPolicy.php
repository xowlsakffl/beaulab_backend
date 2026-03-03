<?php

namespace App\Domains\HospitalVideoRequest\Policies;

use App\Domains\HospitalVideoRequest\Policies\Staff\HospitalVideoRequestForStaffPolicy;
use App\Domains\Staff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\HospitalVideoRequest\Models\HospitalVideoRequest;

final class HospitalVideoRequestPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, HospitalVideoRequest $videoRequest): bool
    {
        return $this->delegate($actor)->view($actor, $videoRequest);
    }

    public function create(mixed $actor): bool
    {
        return $this->delegate($actor)->create($actor);
    }

    public function update(mixed $actor, HospitalVideoRequest $videoRequest): bool
    {
        return $this->delegate($actor)->update($actor, $videoRequest);
    }

    public function delete(mixed $actor, HospitalVideoRequest $videoRequest): bool
    {
        return $this->delegate($actor)->delete($actor, $videoRequest);
    }

    public function cancel(mixed $actor, HospitalVideoRequest $videoRequest): bool
    {
        return $this->delegate($actor)->cancel($actor, $videoRequest);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(HospitalVideoRequestForStaffPolicy::class),
            $actor instanceof AccountUser => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, HospitalVideoRequest $videoRequest): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, HospitalVideoRequest $videoRequest): bool { return false; }
                public function delete(mixed $actor, HospitalVideoRequest $videoRequest): bool { return false; }
                public function cancel(mixed $actor, HospitalVideoRequest $videoRequest): bool { return false; }
            },
            default => new class {
                public function viewAny(mixed $actor): bool { return false; }
                public function view(mixed $actor, HospitalVideoRequest $videoRequest): bool { return false; }
                public function create(mixed $actor): bool { return false; }
                public function update(mixed $actor, HospitalVideoRequest $videoRequest): bool { return false; }
                public function delete(mixed $actor, HospitalVideoRequest $videoRequest): bool { return false; }
                public function cancel(mixed $actor, HospitalVideoRequest $videoRequest): bool { return false; }
            },
        };
    }
}
