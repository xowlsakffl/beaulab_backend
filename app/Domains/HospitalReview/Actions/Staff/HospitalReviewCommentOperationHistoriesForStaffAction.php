<?php

namespace App\Domains\HospitalReview\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\Common\OperationHistory\Dto\OperationHistoryDto;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use Illuminate\Support\Facades\Gate;

final class HospitalReviewCommentOperationHistoriesForStaffAction
{
    public function execute(HospitalReviewComment $comment, array $filters = []): array
    {
        Gate::authorize('view', $comment);

        $operationHistories = $comment->operationHistories()->with('actor')->paginate(
            perPage: (int) ($filters['operation_histories_per_page'] ?? 10),
            pageName: 'operation_histories_page',
            page: (int) ($filters['operation_histories_page'] ?? 1),
        );

        return PaginatedResponse::fromPaginator(
            $operationHistories,
            fn ($history): array => OperationHistoryDto::fromModel($history)->toArray(),
        );
    }
}
