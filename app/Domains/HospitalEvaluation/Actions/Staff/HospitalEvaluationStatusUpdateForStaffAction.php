<?php

namespace App\Domains\HospitalEvaluation\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
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
        $hiddenReason = $status === HospitalEvaluation::STATUS_ACTIVE ? null : $this->normalizeReason($payload['hidden_reason'] ?? null);
        $actor = auth()->user();

        return DB::transaction(function () use ($ids, $status, $hiddenReason, $actor): array {
            $evaluations = $this->query->getForUpdate($ids);
            $existingIds = $evaluations
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->values()
                ->all();

            $lockedIds = $evaluations
                ->filter(static fn (HospitalEvaluation $evaluation): bool => $evaluation->isStatusChangeLocked())
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->values()
                ->all();

            if ($lockedIds !== []) {
                throw new CustomException(
                    ErrorCode::INVALID_REQUEST,
                    sprintf(
                        '자동 블라인드, 게시중단, 본인삭제 상태의 평가는 상태를 변경할 수 없습니다. (ID: %s)',
                        implode(', ', $lockedIds),
                    ),
                );
            }

            $updatedCount = $this->query->update($existingIds, $status);

            foreach ($evaluations as $evaluation) {
                $beforeStatus = (string) $evaluation->status;
                if ($beforeStatus === $status) {
                    continue;
                }

                $this->historyCreateAction->execute(
                    target: $evaluation,
                    action: OperationHistory::ACTION_STATUS_UPDATED,
                    actor: $actor instanceof Model ? $actor : null,
                    field: 'status',
                    beforeValue: $beforeStatus,
                    afterValue: $status,
                    reason: $hiddenReason,
                    metadata: [
                        'before_label' => $beforeStatus === HospitalEvaluation::STATUS_ACTIVE ? '노출' : '미노출',
                        'after_label' => $status === HospitalEvaluation::STATUS_ACTIVE ? '노출' : '미노출',
                        'source' => 'staff.hospital-evaluation.status',
                        'bulk' => count($existingIds) > 1,
                    ],
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
