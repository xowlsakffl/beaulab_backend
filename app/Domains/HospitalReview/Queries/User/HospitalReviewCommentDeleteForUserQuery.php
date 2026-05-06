<?php

namespace App\Domains\HospitalReview\Queries\User;

use App\Domains\HospitalReview\Models\HospitalReviewComment;

final class HospitalReviewCommentDeleteForUserQuery
{
    public function getOwnedForUpdate(int $reviewId, int $commentId, int $authorId): ?HospitalReviewComment
    {
        return HospitalReviewComment::query()
            ->whereKey($commentId)
            ->where('hospital_review_id', $reviewId)
            ->where('author_id', $authorId)
            ->lockForUpdate()
            ->first();
    }

    public function markDeleted(HospitalReviewComment $comment, string $status, string $postStatus): HospitalReviewComment
    {
        $comment->forceFill([
            'status' => $status,
            'post_status' => $postStatus,
        ]);

        if ($comment->isDirty()) {
            $comment->save();
        }

        return $comment->fresh();
    }
}
