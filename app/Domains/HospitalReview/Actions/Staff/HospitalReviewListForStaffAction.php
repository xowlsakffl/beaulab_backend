<?php

namespace App\Domains\HospitalReview\Actions\Staff;

use App\Domains\HospitalReview\Dto\Staff\HospitalReviewForStaffDto;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Queries\Staff\HospitalReviewListForStaffQuery;
use Illuminate\Support\Facades\Gate;

/**
 * HospitalReviewListForStaffAction 역할 정의.
 * 병의원 후기 도메인의 Action 계층으로, 관리자 목록 조회 흐름을 조합한다.
 */
final class HospitalReviewListForStaffAction
{
    public function __construct(
        private readonly HospitalReviewListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalReview::class);

        $paginator = $this->query->paginate($filters);

        return [
            'items' => collect($paginator->items())
                ->map(fn ($review) => HospitalReviewForStaffDto::fromModel($review)->toArray())
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }
}
