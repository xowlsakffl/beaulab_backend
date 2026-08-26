<?php

namespace App\Domains\HospitalReview\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\HospitalReview\Policies\Staff\HospitalReviewCommentForStaffPolicy;

final class HospitalReviewCommentPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, HospitalReviewComment $comment): bool
    {
        return $this->delegate($actor)->view($actor, $comment);
    }

    public function update(mixed $actor, ?HospitalReviewComment $comment = null): bool
    {
        return $this->delegate($actor)->update($actor, $comment);
    }

    public function updateStatus(mixed $actor, ?HospitalReviewComment $comment = null): bool
    {
        return $actor instanceof AccountStaff
            && app(HospitalReviewCommentForStaffPolicy::class)->updateStatus($actor, $comment);
    }

    public function delete(mixed $actor, HospitalReviewComment $comment): bool
    {
        return $this->delegate($actor)->delete($actor, $comment);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(HospitalReviewCommentForStaffPolicy::class),
            $actor instanceof AccountUser => new class
            {
                public function viewAny(mixed $actor): bool
                {
                    return false;
                }

                public function view(mixed $actor, HospitalReviewComment $comment): bool
                {
                    return false;
                }

                public function update(mixed $actor, ?HospitalReviewComment $comment = null): bool
                {
                    return false;
                }

                public function delete(mixed $actor, HospitalReviewComment $comment): bool
                {
                    return $actor instanceof AccountUser
                        && (int) $actor->id === (int) $comment->author_id;
                }
            },
            default => new class
            {
                public function viewAny(mixed $actor): bool
                {
                    return false;
                }

                public function view(mixed $actor, HospitalReviewComment $comment): bool
                {
                    return false;
                }

                public function update(mixed $actor, ?HospitalReviewComment $comment = null): bool
                {
                    return false;
                }

                public function delete(mixed $actor, HospitalReviewComment $comment): bool
                {
                    return false;
                }
            },
        };
    }
}
