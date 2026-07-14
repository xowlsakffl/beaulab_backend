<?php

namespace App\Domains\HospitalEventAd\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\Common\OperationHistory\Dto\OperationHistoryDto;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use Illuminate\Support\Facades\Gate;

final class HospitalEventAdOperationHistoriesForStaffAction
{
    public function execute(HospitalEventAd $ad, array $filters = []): array
    {
        Gate::authorize('view', $ad);

        $operationHistories = $ad->operationHistories()->with('actor')->paginate(
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
