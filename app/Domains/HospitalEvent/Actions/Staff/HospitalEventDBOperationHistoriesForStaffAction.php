<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\Common\OperationHistory\Dto\OperationHistoryDto;
use App\Domains\HospitalEvent\Models\HospitalEventDB;
use Illuminate\Support\Facades\Gate;

final class HospitalEventDBOperationHistoriesForStaffAction
{
    public function execute(HospitalEventDB $eventDB, array $filters = []): array
    {
        $eventDB->loadMissing('event:id,hospital_id,status,allow_status');
        if ($eventDB->event !== null) {
            Gate::authorize('view', $eventDB->event);
        }

        $operationHistories = $eventDB->operationHistories()->with('actor')->paginate(
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
