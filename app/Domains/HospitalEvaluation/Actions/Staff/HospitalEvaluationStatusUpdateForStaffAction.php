<?php

namespace App\Domains\HospitalEvaluation\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Domains\HospitalEvaluation\Queries\Staff\HospitalEvaluationStatusUpdateForStaffQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEvaluationStatusUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalEvaluationStatusUpdateForStaffQuery $query,
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('update', HospitalEvaluation::class);

        $ids = collect($payload['ids'] ?? [])
            ->map(static fn (int|string $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
        $status = (string) $payload['status'];
        $historyReason = $status === HospitalEvaluation::STATUS_ACTIVE ? null : $this->normalizeReason($payload['hidden_reason'] ?? null);
        $actor = auth()->user();

        return DB::transaction(function () use ($ids, $status, $historyReason, $actor): array {
            $evaluations = $this->query->getForUpdate($ids);
            if ($evaluations->contains(fn (HospitalEvaluation $evaluation): bool => $evaluation->isStatusChangeLocked())) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '신고 처리 상태가 자동차단 또는 노출중지인 평가는 노출여부를 변경할 수 없습니다.');
            }

            $existingIds = $evaluations
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->values()
                ->all();

            $updatedCount = $this->query->update($existingIds, $status);
            HospitalEvaluation::refreshHospitalRatingAggregates(
                $evaluations
                    ->pluck('hospital_id')
                    ->map(static fn (int|string $hospitalId): int => (int) $hospitalId)
                    ->unique()
                    ->values()
                    ->all()
            );

            foreach ($evaluations as $evaluation) {
                $beforeStatus = (string) $evaluation->status;
                if ($beforeStatus === $status) {
                    continue;
                }

                $this->historyCreateAction->execute(
                    target: $evaluation,
                    action: OperationHistory::ACTION_STATUS_UPDATED,
                    actor: $actor instanceof Model ? $actor : null,
                    reason: $historyReason,
                    metadata: [
                        'source' => 'staff.hospital_evaluation.status',
                        'bulk' => count($existingIds) > 1,
                    ],
                    changes: OperationHistoryChangeSetBuilder::single(
                        key: 'status',
                        label: '노출여부',
                        before: $beforeStatus,
                        after: $status,
                        beforeDisplay: $beforeStatus === HospitalEvaluation::STATUS_ACTIVE ? '노출' : '미노출',
                        afterDisplay: $status === HospitalEvaluation::STATUS_ACTIVE ? '노출' : '미노출',
                    ),
                );
            }

            return [
                'updated_count' => $updatedCount,
                'status' => $status,
                'ids' => $existingIds,
            ];
        });
    }

    private function normalizeReason(mixed $reason): ?string
    {
        $reason = trim((string) $reason);

        return $reason === '' ? null : $reason;
    }
}
