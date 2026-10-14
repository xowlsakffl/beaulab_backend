<?php

namespace App\Domains\BannerExposure\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\BannerExposure\Models\BannerExposure;

final class BannerExposurePolicy
{
    public function viewAny(mixed $actor, string $placement = 'MAIN_POPUP'): bool
    {
        return $this->allows($actor, $placement, 'show');
    }

    public function view(mixed $actor, BannerExposure $banner): bool
    {
        return $this->viewAny($actor, $banner->placement);
    }

    public function create(mixed $actor, string $placement): bool
    {
        return $this->allows($actor, $placement, 'create');
    }

    public function update(mixed $actor, BannerExposure $banner): bool
    {
        return $this->allows($actor, $banner->placement, 'update');
    }

    public function reorder(mixed $actor, string $placement): bool
    {
        return $this->allows($actor, $placement, 'update');
    }

    public function delete(mixed $actor, BannerExposure $banner): bool
    {
        return $this->allows($actor, $banner->placement, 'delete');
    }

    private function allows(mixed $actor, string $placement, string $action): bool
    {
        if (! $actor instanceof AccountStaff || ! in_array($placement, BannerExposure::PLACEMENTS, true)) {
            return false;
        }
        $scope = in_array($placement, ['CONSULT_MEMO', 'SEARCH'], true) ? 'other_exposure' : 'banner_exposure';

        return $actor->can("beaulab.{$scope}.{$action}");
    }
}
