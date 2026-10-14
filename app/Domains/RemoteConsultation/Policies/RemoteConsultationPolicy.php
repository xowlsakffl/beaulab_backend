<?php

namespace App\Domains\RemoteConsultation\Policies;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\RemoteConsultation\Models\RemoteConsultation;

final class RemoteConsultationPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $actor instanceof AccountStaff && $actor->can(AccessPermissions::BEAULAB_REMOTE_CONSULTATION_SHOW);
    }

    public function view(mixed $actor, RemoteConsultation $consultation): bool
    {
        return $this->viewAny($actor);
    }

    public function approve(mixed $actor, RemoteConsultation $consultation): bool
    {
        return $this->viewAny($actor) && $actor->can(AccessPermissions::BEAULAB_REMOTE_CONSULTATION_APPROVE);
    }

    public function assign(mixed $actor, RemoteConsultation $consultation): bool
    {
        return $this->viewAny($actor) && $actor->can(AccessPermissions::BEAULAB_REMOTE_CONSULTATION_ASSIGN);
    }
}
