<?php

namespace App\Domains\BannerExposure\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\BannerExposure\Models\BannerExposure;

final class BannerExposurePolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $actor instanceof AccountStaff && $actor->can('beaulab.banner_exposure.show');
    }

    public function view(mixed $actor, BannerExposure $banner): bool
    {
        return $this->viewAny($actor);
    }

    public function create(mixed $actor): bool
    {
        return $actor instanceof AccountStaff && $actor->can('beaulab.banner_exposure.create');
    }

    public function update(mixed $actor, ?BannerExposure $banner = null): bool
    {
        return $actor instanceof AccountStaff && $actor->can('beaulab.banner_exposure.update');
    }

    public function delete(mixed $actor, BannerExposure $banner): bool
    {
        return $actor instanceof AccountStaff && $actor->can('beaulab.banner_exposure.delete');
    }
}
