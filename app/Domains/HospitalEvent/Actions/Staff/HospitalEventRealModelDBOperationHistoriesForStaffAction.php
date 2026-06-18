<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\Common\OperationHistory\Dto\OperationHistoryDto;
use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use Illuminate\Support\Facades\Gate;

final class HospitalEventRealModelDBOperationHistoriesForStaffAction
{
    public function execute(HospitalEventRealModelDB $application, array $filters = []): array
    {
        $application->loadMissing('event:id,hospital_id,status,allow_status');
        if ($application->event !== null) {
            Gate::authorize('view', $application->event);
        }

        $operationHistories = $application->operationHistories()->with('actor')->paginate(
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
