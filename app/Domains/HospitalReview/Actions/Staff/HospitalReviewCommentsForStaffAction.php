<?php

namespace App\Domains\HospitalReview\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\HospitalReview\Dto\Staff\HospitalReviewCommentForStaffDetailDto;
use App\Domains\HospitalReview\Models\HospitalReview;
use Illuminate\Support\Facades\Gate;

final class HospitalReviewCommentsForStaffAction
{
    public function execute(HospitalReview $review, array $filters = []): array
    {
        Gate::authorize('view', $review);

        $comments = PaginatedResponse::paginateWithFallback(
            queryFactory: fn () => $review->comments()
                ->with(['author', 'operationHistories.actor', 'mentions.mentionedUser']),
            perPage: (int) ($filters['comments_per_page'] ?? 10),
            pageName: 'comments_page',
            page: (int) ($filters['comments_page'] ?? 1),
        );

        return PaginatedResponse::fromPaginator(
            $comments,
            fn ($comment): array => HospitalReviewCommentForStaffDetailDto::fromModel($comment)->toArray(),
        );
    }
}
