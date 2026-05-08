<?php

namespace App\Domains\HospitalEvaluation\Actions\Staff;

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

        return [
            'items' => collect($paginator->items())
                ->map(fn ($evaluation) => HospitalEvaluationForStaffDto::fromModel($evaluation)->toArray())
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
