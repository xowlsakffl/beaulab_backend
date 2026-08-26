<?php

namespace App\Domains\Talk\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Queries\Staff\TalkStatusUpdateForStaffQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * 토크 다중 상태 변경 유스케이스.
 */
final class TalkStatusUpdateForStaffAction
{
    public function __construct(
        private readonly TalkStatusUpdateForStaffQuery $query,
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('updateStatus', Talk::class);

        $ids = collect($payload['ids'] ?? [])
            ->map(static fn (int|string $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
        $status = (string) $payload['status'];
        $historyReason = $status === Talk::STATUS_ACTIVE ? null : $this->normalizeReason($payload['hidden_reason'] ?? null);
        $actor = auth()->user();

        return DB::transaction(function () use ($ids, $status, $historyReason, $actor): array {
            $talks = $this->query->getForUpdate($ids);
            if ($talks->contains(fn (Talk $talk): bool => $talk->isStatusChangeLocked())) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '신고 처리 상태가 자동차단 또는 노출중지인 게시물은 공개여부를 변경할 수 없습니다.');
            }

            $existingIds = $talks
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->values()
                ->all();

            $updatedCount = $this->query->update($existingIds, $status);

            foreach ($talks as $talk) {
                $beforeStatus = (string) $talk->status;
                if ($beforeStatus === $status) {
                    continue;
                }

                $this->historyCreateAction->execute(
                    target: $talk,
                    action: OperationHistory::ACTION_STATE_UPDATED,
                    actor: $actor instanceof Model ? $actor : null,
                    reason: $historyReason,
                    metadata: [
                        'source' => 'staff.talk.status',
                        'bulk' => count($existingIds) > 1,
                    ],
                    changes: OperationHistoryChangeSetBuilder::single(
                        key: 'status',
                        label: '공개여부',
                        before: $beforeStatus,
                        after: $status,
                        beforeDisplay: $beforeStatus === Talk::STATUS_ACTIVE ? '노출' : '미노출',
                        afterDisplay: $status === Talk::STATUS_ACTIVE ? '노출' : '미노출',
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
