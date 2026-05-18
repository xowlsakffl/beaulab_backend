<?php

namespace App\Domains\HospitalEvaluation\Actions\Staff;

use App\Common\Support\PaginatedResponse;
use App\Domains\HospitalEvaluation\Dto\Staff\HospitalEvaluationForStaffDto;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Domains\HospitalEvaluation\Queries\Staff\HospitalEvaluationListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalEvaluationListForStaffAction
{
    public function __construct(
        private readonly HospitalEvaluationListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalEvaluation::class);

        $paginator = $this->query->paginate($filters);

        return PaginatedResponse::fromPaginator(
            $paginator,
            fn ($evaluation): array => HospitalEvaluationForStaffDto::fromModel($evaluation)->toArray(),
        );
    }
}
