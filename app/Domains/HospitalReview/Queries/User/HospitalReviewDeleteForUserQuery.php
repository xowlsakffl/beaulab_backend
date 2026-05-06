<?php

namespace App\Domains\HospitalReview\Queries\User;

use App\Domains\HospitalReview\Models\HospitalReview;

final class HospitalReviewDeleteForUserQuery
{
    public function getOwnedForUpdate(int $reviewId, int $authorId): ?HospitalReview
    {
        return HospitalReview::query()
            ->whereKey($reviewId)
            ->where('author_id', $authorId)
            ->lockForUpdate()
            ->first();
    }

    public function markDeleted(HospitalReview $review, string $status, string $postStatus): HospitalReview
    {
        $review->forceFill([
            'status' => $status,
            'post_status' => $postStatus,
        ]);

        if ($review->isDirty()) {
            $review->save();
        }

        return $review->fresh();
    }
}
