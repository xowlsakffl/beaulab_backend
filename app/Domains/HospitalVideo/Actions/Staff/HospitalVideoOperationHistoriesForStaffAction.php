<?php

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\Common\OperationHistory\Dto\OperationHistoryDto;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Support\Facades\Gate;

final class HospitalVideoOperationHistoriesForStaffAction
{
    public function execute(HospitalVideo $video, array $filters = []): array
    {
        Gate::authorize('view', $video);

        $operationHistories = $video->operationHistories()->with('actor')->paginate(
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
