<?php

namespace App\Domains\HospitalPromotion\Policies;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalPromotion\Models\HospitalPromotion;

final class HospitalPromotionPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $actor instanceof AccountStaff && $actor->can(AccessPermissions::BEAULAB_HOSPITAL_PROMOTION_SHOW);
    }

    public function view(mixed $actor, HospitalPromotion $promotion): bool
    {
        return $this->viewAny($actor);
    }

    public function create(mixed $actor): bool
    {
        return $actor instanceof AccountStaff && $actor->can(AccessPermissions::BEAULAB_HOSPITAL_PROMOTION_CREATE);
    }

    public function update(mixed $actor, HospitalPromotion $promotion): bool
    {
        return $actor instanceof AccountStaff && $actor->can(AccessPermissions::BEAULAB_HOSPITAL_PROMOTION_UPDATE);
    }

    public function updateStatus(mixed $actor, ?HospitalPromotion $promotion = null): bool
    {
        return $actor instanceof AccountStaff && $actor->can(AccessPermissions::BEAULAB_HOSPITAL_PROMOTION_STATUS_UPDATE);
    }

    public function manageEditorImages(mixed $actor): bool
    {
        return $actor instanceof AccountStaff && ($this->create($actor) || $actor->can(AccessPermissions::BEAULAB_HOSPITAL_PROMOTION_UPDATE));
    }

    public function checkAvailability(mixed $actor): bool
    {
        return $this->viewAny($actor) || $this->manageEditorImages($actor);
    }

    public function viewPublic(mixed $actor): bool
    {
        return $actor instanceof AccountHospital;
    }
}
