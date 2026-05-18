<?php

namespace App\Domains\Talk\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\Common\OperationHistory\Dto\OperationHistoryDto;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Support\Facades\Gate;

final class TalkCommentOperationHistoriesForStaffAction
{
    public function execute(TalkComment $comment, array $filters = []): array
    {
        Gate::authorize('view', $comment);

        $operationHistories = PaginatedResponse::paginateWithFallback(
            queryFactory: fn () => $comment->operationHistories()->with('actor'),
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
