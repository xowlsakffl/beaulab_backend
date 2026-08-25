<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Policies;

use App\Domains\AccountHospital\Models\HospitalAccountInvitation;
use App\Domains\AccountHospital\Policies\Staff\HospitalAccountInvitationForStaffPolicy;
use App\Domains\AccountStaff\Models\AccountStaff;

final class HospitalAccountInvitationPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, HospitalAccountInvitation $invitation): bool
    {
        return $this->delegate($actor)->view($actor, $invitation);
    }

    public function create(mixed $actor): bool
    {
        return $this->delegate($actor)->create($actor);
    }

    public function delete(mixed $actor, HospitalAccountInvitation $invitation): bool
    {
        return $this->delegate($actor)->delete($actor, $invitation);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(HospitalAccountInvitationForStaffPolicy::class),
            default => new class
            {
                public function viewAny(mixed $actor): bool
                {
                    return false;
                }

                public function view(mixed $actor, HospitalAccountInvitation $invitation): bool
                {
                    return false;
                }

                public function create(mixed $actor): bool
                {
                    return false;
                }

                public function delete(mixed $actor, HospitalAccountInvitation $invitation): bool
                {
                    return false;
                }
            },
        };
    }
}
