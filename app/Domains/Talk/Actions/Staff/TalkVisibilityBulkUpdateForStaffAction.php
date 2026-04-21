<?php

namespace App\Domains\Talk\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Actions\OperationHistory\OperationHistoryCreateAction;
use App\Domains\Common\Models\OperationHistory\OperationHistory;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Queries\Staff\TalkVisibilityBulkUpdateForStaffQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * 토크 다중 노출 상태 변경 유스케이스.
 */
final class TalkVisibilityBulkUpdateForStaffAction
{
    public function __construct(
        private readonly TalkVisibilityBulkUpdateForStaffQuery $query,
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('update', Talk::class);

        $ids = collect($payload['ids'] ?? [])
            ->map(static fn (int|string $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
        $status = (string) $payload['status'];
        $hiddenReason = $status === Talk::STATUS_ACTIVE ? null : $this->normalizeReason($payload['hidden_reason'] ?? null);
        $actor = auth()->user();

        return DB::transaction(function () use ($ids, $status, $hiddenReason, $actor): array {
            $talks = $this->query->getForUpdate($ids);
            $existingIds = $talks
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->values()
                ->all();

            $lockedIds = $talks
                ->filter(static fn (Talk $talk): bool => $talk->isVisibilityChangeLocked())
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->values()
                ->all();

            if ($lockedIds !== []) {
                throw new CustomException(
                    ErrorCode::INVALID_REQUEST,
                    sprintf(
                        '자동 블라인드, 게시중단, 본인삭제 상태의 토크는 노출 상태를 변경할 수 없습니다. (ID: %s)',
                        implode(', ', $lockedIds),
                    ),
                );
            }

            $updatedCount = $this->query->update($existingIds, $status);

            foreach ($talks as $talk) {
                $beforeStatus = (string) $talk->status;
                if ($beforeStatus === $status) {
                    continue;
                }

                $this->historyCreateAction->execute(
                    target: $talk,
                    action: OperationHistory::ACTION_VISIBILITY_UPDATED,
                    actor: $actor instanceof Model ? $actor : null,
                    field: 'status',
                    beforeValue: $beforeStatus,
                    afterValue: $status,
                    reason: $hiddenReason,
                    metadata: [
                        'before_label' => $beforeStatus === Talk::STATUS_ACTIVE ? '노출' : '미노출',
                        'after_label' => $status === Talk::STATUS_ACTIVE ? '노출' : '미노출',
                        'source' => 'staff.talk.visibility',
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
