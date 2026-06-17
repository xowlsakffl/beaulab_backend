<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\Common\OperationHistory\Dto\OperationHistoryDto;
use App\Domains\HospitalEvent\Models\HospitalEventConsultation;
use Illuminate\Support\Facades\Gate;

final class HospitalEventConsultationOperationHistoriesForStaffAction
{
    public function execute(HospitalEventConsultation $consultation, array $filters = []): array
    {
        $consultation->loadMissing('event:id,hospital_id,status,allow_status');
        if ($consultation->event !== null) {
            Gate::authorize('view', $consultation->event);
        }

        $operationHistories = $consultation->operationHistories()->with('actor')->paginate(
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
