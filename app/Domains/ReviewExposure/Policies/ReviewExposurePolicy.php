<?php

namespace App\Domains\ReviewExposure\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\ReviewExposure\Models\ReviewExposure;

final class ReviewExposurePolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $actor instanceof AccountStaff && $actor->can('beaulab.review_exposure.show');
    }

    public function view(mixed $actor, ReviewExposure $exposure): bool
    {
        return $this->viewAny($actor);
    }

    public function create(mixed $actor): bool
    {
        return $actor instanceof AccountStaff && $actor->can('beaulab.review_exposure.create');
    }

    public function update(mixed $actor, ?ReviewExposure $exposure = null): bool
    {
        return $actor instanceof AccountStaff && $actor->can('beaulab.review_exposure.update');
    }
}
