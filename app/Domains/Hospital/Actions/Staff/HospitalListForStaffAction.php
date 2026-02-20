<?php

namespace App\Domains\Hospital\Actions\Staff;

use App\Domains\Hospital\Dto\Staff\HospitalForStaffDto;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Queries\Staff\HospitalListForStaffQuery;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class HospitalListForStaffAction
{
    public function __construct(
        private readonly HospitalListForStaffQuery $query,
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
        Gate::authorize('viewAny', Hospital::class);

        Log::info('병원 목록 조회 실행', [
            'filters' => $filters,
        ]);

        $paginator = $this->query->paginate($filters);

        $items = collect($paginator->items())
            ->map(fn ($hospital) => HospitalForStaffDto::fromModel($hospital)->toArray())
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
