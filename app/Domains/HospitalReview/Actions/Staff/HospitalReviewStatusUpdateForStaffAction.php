<?php

namespace App\Domains\HospitalReview\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Queries\Staff\HospitalReviewStatusUpdateForStaffQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalReviewStatusUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalReviewStatusUpdateForStaffQuery $query,
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('update', HospitalReview::class);

        $ids = collect($payload['ids'] ?? [])
            ->map(static fn (int|string $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
        $status = (string) $payload['status'];
        $historyReason = $status === HospitalReview::STATUS_ACTIVE ? null : $this->normalizeReason($payload['hidden_reason'] ?? null);
        $actor = auth()->user();

        return DB::transaction(function () use ($ids, $status, $historyReason, $actor): array {
            $reviews = $this->query->getForUpdate($ids);
            if ($reviews->contains(fn (HospitalReview $review): bool => $review->isStatusChangeLocked())) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '신고 처리 상태가 자동차단 또는 노출중지인 후기는 노출여부를 변경할 수 없습니다.');
            }

            $existingIds = $reviews
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->values()
                ->all();

            $updatedCount = $this->query->update($existingIds, $status);

            foreach ($reviews as $review) {
                $beforeStatus = (string) $review->status;
                if ($beforeStatus === $status) {
                    continue;
                }

                $this->historyCreateAction->execute(
                    target: $review,
                    action: OperationHistory::ACTION_STATE_UPDATED,
                    actor: $actor instanceof Model ? $actor : null,
                    reason: $historyReason,
                    metadata: [
                        'source' => 'staff.hospital_review.status',
                        'bulk' => count($existingIds) > 1,
                    ],
                    changes: OperationHistoryChangeSetBuilder::single(
                        key: 'status',
                        label: '노출여부',
                        before: $beforeStatus,
                        after: $status,
                        beforeDisplay: $beforeStatus === HospitalReview::STATUS_ACTIVE ? '노출' : '미노출',
                        afterDisplay: $status === HospitalReview::STATUS_ACTIVE ? '노출' : '미노출',
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
