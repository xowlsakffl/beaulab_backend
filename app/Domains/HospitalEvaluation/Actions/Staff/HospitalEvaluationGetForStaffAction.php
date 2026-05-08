<?php

namespace App\Domains\HospitalEvaluation\Actions\Staff;

use App\Domains\Common\OperationHistory\Dto\OperationHistoryDto;
use App\Domains\HospitalEvaluation\Dto\Staff\HospitalEvaluationForStaffDetailDto;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

final class HospitalEvaluationGetForStaffAction
{
    public function execute(HospitalEvaluation $evaluation, array $filters = []): array
    {
        Gate::authorize('view', $evaluation);

        $evaluation->load([
            'author',
            'hospital.businessRegistration',
            'doctor',
            'categories',
            'images',
            'receiptImages',
        ]);

        $operationHistories = $evaluation->operationHistories()
            ->with('actor')
            ->paginate(
                perPage: (int) ($filters['operation_histories_per_page'] ?? 15),
                pageName: 'operation_histories_page',
                page: (int) ($filters['operation_histories_page'] ?? 1),
            );

        return [
            'evaluation' => HospitalEvaluationForStaffDetailDto::fromModel(
                $evaluation,
                operationHistories: $this->paginated(
                    $operationHistories,
                    fn ($history): array => OperationHistoryDto::fromModel($history)->toArray(),
                ),
            )->toArray(),
        ];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    private function paginated(LengthAwarePaginator $paginator, callable $mapper): array
    {
        return [
            'items' => collect($paginator->items())
                ->map($mapper)
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
