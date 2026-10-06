<?php

namespace App\Domains\VideoExposure\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\VideoExposure\Models\VideoExposure;

final class VideoExposurePolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $actor instanceof AccountStaff && $actor->can('beaulab.video_exposure.show');
    }

    public function view(mixed $actor, VideoExposure $exposure): bool
    {
        return $this->viewAny($actor);
    }

    public function create(mixed $actor): bool
    {
        return $actor instanceof AccountStaff && $actor->can('beaulab.video_exposure.create');
    }

    public function update(mixed $actor, ?VideoExposure $exposure = null): bool
    {
        return $actor instanceof AccountStaff && $actor->can('beaulab.video_exposure.update');
    }
}
