<?php

namespace App\Domains\Beauty\Actions\Staff;

use App\Domains\Beauty\Dto\Staff\BeautyForStaffDto;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\Beauty\Queries\Staff\BeautyListForStaffQuery;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class BeautyListForStaffAction
{
    public function __construct(
        private readonly BeautyListForStaffQuery $query,
    ) {}

    /**
     * @param array{
     *   q?: string|null,
     *   start_date?: string|null,
     *   end_date?: string|null,
     *   status?: array<int, string>|null,
     *    allow_status?: array<int, string>|null,
     *   sort?: string,
     *   direction?: 'asc'|'desc',
     *   per_page?: int
     * } $filters
     */
    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', Beauty::class);

        Log::info('뷰티업체 목록 조회 실행', [
            'filters' => $filters,
        ]);

        $paginator = $this->query->paginate($filters);

        $items = collect($paginator->items())
            ->map(fn ($beauty) => BeautyForStaffDto::fromModel($beauty)->toArray())
            ->values()
            ->all();

        return [
            'items' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ];
    }
}
