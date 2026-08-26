<?php

namespace App\Domains\HospitalReview\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Policies\Staff\HospitalReviewForStaffPolicy;

final class HospitalReviewPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, HospitalReview $review): bool
    {
        return $this->delegate($actor)->view($actor, $review);
    }

    public function update(mixed $actor, ?HospitalReview $review = null): bool
    {
        return $this->delegate($actor)->update($actor, $review);
    }

    public function updateStatus(mixed $actor, ?HospitalReview $review = null): bool
    {
        return $actor instanceof AccountStaff
            && app(HospitalReviewForStaffPolicy::class)->updateStatus($actor, $review);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(HospitalReviewForStaffPolicy::class),
            $actor instanceof AccountUser => new class
            {
                public function viewAny(mixed $actor): bool
                {
                    return false;
                }

                public function view(mixed $actor, HospitalReview $review): bool
                {
                    return false;
                }

                public function update(mixed $actor, ?HospitalReview $review = null): bool
                {
                    return false;
                }
            },
            default => new class
            {
                public function viewAny(mixed $actor): bool
                {
                    return false;
                }

                public function view(mixed $actor, HospitalReview $review): bool
                {
                    return false;
                }

                public function update(mixed $actor, ?HospitalReview $review = null): bool
                {
                    return false;
                }
            },
        };
    }
}
