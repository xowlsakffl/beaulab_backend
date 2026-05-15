<?php

namespace App\Domains\Talk\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\Talk\Dto\Staff\TalkForStaffDetailDto;
use App\Domains\Talk\Models\Talk;
use Illuminate\Support\Facades\Gate;

final class TalkOperationHistoriesForStaffAction
{
    public function execute(Talk $talk, array $filters = []): array
    {
        Gate::authorize('view', $talk);

        $operationHistories = PaginatedResponse::paginateWithFallback(
            queryFactory: fn () => $talk->operationHistories()->with('actor'),
            perPage: (int) ($filters['operation_histories_per_page'] ?? 10),
            pageName: 'operation_histories_page',
            page: (int) ($filters['operation_histories_page'] ?? 1),
        );

        return PaginatedResponse::fromPaginator(
            $operationHistories,
            fn ($history): array => TalkForStaffDetailDto::operationHistory($history),
        );
    }
}
