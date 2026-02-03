<?php

namespace App\Domains\Hospital\Actions\Admin;

use App\Domains\Hospital\Dto\Admin\HospitalListForStaffDto;
use App\Domains\Hospital\Queries\Admin\HospitalListForStaffQuery;

final class HospitalListForStaffAction
{
    public function __construct(
        private readonly HospitalListForStaffQuery $query,
    ) {}

    /**
     * @param array{
     *   q?: string|null,
     *   status?: string|null,
     *   allow_status?: string|null,
     *   sort?: string,
     *   direction?: 'asc'|'desc',
     *   per_page?: int
     * } $filters
     */
    public function execute(array $filters): array
    {
        // 1) Query 호출
        $paginator = $this->query->paginate($filters);

        // 2) items()를 DTO로 변환 (응답 필드 통제)
        $items = collect($paginator->items())
            ->map(fn ($hospital) => HospitalListForStaffDto::fromModel($hospital)->toArray())
            ->values()
            ->all();

        // 3) meta 구성 (프론트 페이지네이션에 필요)
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
