<?php

namespace App\Domains\HospitalReview\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalReview\Models\HospitalReviewComment;

final class HospitalReviewCommentForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_REVIEW_SHOW);
    }

    public function view(AccountStaff $actor, HospitalReviewComment $comment): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_REVIEW_SHOW);
    }

    public function update(AccountStaff $actor, ?HospitalReviewComment $comment = null): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_REVIEW_UPDATE);
    }

    public function updateStatus(AccountStaff $actor, ?HospitalReviewComment $comment = null): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_REVIEW_STATUS_UPDATE);
    }

    public function delete(AccountStaff $actor, HospitalReviewComment $comment): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_REVIEW_UPDATE);
    }
}
