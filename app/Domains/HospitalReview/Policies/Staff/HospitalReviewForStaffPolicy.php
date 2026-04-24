<?php

namespace App\Domains\HospitalReview\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalReview\Models\HospitalReview;

final class HospitalReviewForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_REVIEW_SHOW);
    }

    public function view(AccountStaff $actor, HospitalReview $review): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_REVIEW_SHOW);
    }

    public function update(AccountStaff $actor, ?HospitalReview $review = null): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_REVIEW_UPDATE);
    }
}
