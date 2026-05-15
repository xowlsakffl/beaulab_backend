<?php

namespace App\Domains\HospitalEvaluation\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\Common\OperationHistory\Dto\OperationHistoryDto;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use Illuminate\Support\Facades\Gate;

final class HospitalEvaluationOperationHistoriesForStaffAction
{
    public function execute(HospitalEvaluation $evaluation, array $filters = []): array
    {
        Gate::authorize('view', $evaluation);

        $operationHistories = PaginatedResponse::paginateWithFallback(
            queryFactory: fn () => $evaluation->operationHistories()->with('actor'),
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
