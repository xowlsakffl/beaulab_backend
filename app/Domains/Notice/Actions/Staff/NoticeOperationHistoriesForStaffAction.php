<?php

namespace App\Domains\Notice\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\Common\OperationHistory\Dto\OperationHistoryDto;
use App\Domains\Notice\Models\Notice;
use Illuminate\Support\Facades\Gate;

final class NoticeOperationHistoriesForStaffAction
{
    public function execute(Notice $notice, array $filters = []): array
    {
        Gate::authorize('view', $notice);

        $histories = $notice->operationHistories()->with(['actor', 'changes'])->paginate(
            perPage: (int) ($filters['operation_histories_per_page'] ?? 10),
            pageName: 'operation_histories_page',
            page: (int) ($filters['operation_histories_page'] ?? 1),
        );

        return PaginatedResponse::fromPaginator(
            $histories,
            fn ($history): array => OperationHistoryDto::fromModel($history)->toArray(),
        );
    }
}
