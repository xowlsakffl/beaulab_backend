<?php

namespace App\Domains\EventExposure\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\EventExposure\Models\EventExposure;

final class EventExposurePolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $actor instanceof AccountStaff && $actor->can('beaulab.event_exposure.show');
    }

    public function view(mixed $actor, EventExposure $exposure): bool
    {
        return $this->viewAny($actor);
    }

    public function create(mixed $actor): bool
    {
        return $actor instanceof AccountStaff && $actor->can('beaulab.event_exposure.create');
    }

    public function update(mixed $actor, ?EventExposure $exposure = null): bool
    {
        return $actor instanceof AccountStaff && $actor->can('beaulab.event_exposure.update');
    }
}
