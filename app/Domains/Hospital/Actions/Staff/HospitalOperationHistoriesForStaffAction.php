<?php

namespace App\Domains\Hospital\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\Common\OperationHistory\Dto\OperationHistoryDto;
use App\Domains\Hospital\Models\Hospital;
use Illuminate\Support\Facades\Gate;

final class HospitalOperationHistoriesForStaffAction
{
    public function execute(Hospital $hospital, array $filters = []): array
    {
        Gate::authorize('view', $hospital);

        $operationHistories = $hospital->operationHistories()->with('actor')->paginate(
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
