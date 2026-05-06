<?php

namespace App\Domains\HospitalReview\Queries\User;

use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\HospitalReview\Models\HospitalReviewCommentMention;

final class HospitalReviewCommentCreateForUserQuery
{
    public function getReviewForUpdate(int $reviewId): ?HospitalReview
    {
        return HospitalReview::query()
            ->whereKey($reviewId)
            ->lockForUpdate()
            ->first();
    }

    public function getParentForUpdate(int $reviewId, int $parentId): ?HospitalReviewComment
    {
        return HospitalReviewComment::query()
            ->whereKey($parentId)
            ->where('hospital_review_id', $reviewId)
            ->lockForUpdate()
            ->first(['id', 'hospital_review_id', 'parent_id', 'status', 'post_status']);
    }

    public function create(array $payload): HospitalReviewComment
    {
        return HospitalReviewComment::query()->create([
            'hospital_review_id' => (int) $payload['hospital_review_id'],
            'parent_id' => $payload['parent_id'] ?? null,
            'author_id' => (int) $payload['author_id'],
            'content' => (string) $payload['content'],
            'status' => HospitalReviewComment::STATUS_ACTIVE,
            'post_status' => HospitalReviewComment::POST_STATUS_NORMAL,
            'author_ip' => $payload['author_ip'] ?? null,
            'like_count' => 0,
        ]);
    }

    public function createMention(HospitalReviewComment $comment, array $payload): HospitalReviewCommentMention
    {
        return $comment->mentions()->create([
            'mentioned_user_id' => (int) $payload['mentioned_user_id'],
            'mentioned_by_user_id' => isset($payload['mentioned_by_user_id'])
                ? (int) $payload['mentioned_by_user_id']
                : null,
            'mention_text' => $payload['mention_text'] ?? null,
            'start_offset' => isset($payload['start_offset']) ? (int) $payload['start_offset'] : null,
            'end_offset' => isset($payload['end_offset']) ? (int) $payload['end_offset'] : null,
        ]);
    }

    public function incrementReviewCommentCount(HospitalReview $review): void
    {
        HospitalReview::query()
            ->whereKey((int) $review->id)
            ->increment('comment_count');
    }
}
