<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\Common\OperationHistory\Dto\OperationHistoryDto;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use Illuminate\Support\Facades\Gate;

final class HospitalEventOperationHistoriesForStaffAction
{
    public function execute(HospitalEvent $event, array $filters = []): array
    {
        Gate::authorize('view', $event);

        $operationHistories = $event->operationHistories()->with('actor')->paginate(
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
