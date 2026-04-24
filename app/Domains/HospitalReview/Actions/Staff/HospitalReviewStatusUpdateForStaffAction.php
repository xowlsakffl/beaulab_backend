<?php

namespace App\Domains\HospitalReview\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Actions\OperationHistory\OperationHistoryCreateAction;
use App\Domains\Common\Models\OperationHistory\OperationHistory;
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
        $hiddenReason = $status === HospitalReview::STATUS_ACTIVE ? null : $this->normalizeReason($payload['hidden_reason'] ?? null);
        $actor = auth()->user();

        return DB::transaction(function () use ($ids, $status, $hiddenReason, $actor): array {
            $reviews = $this->query->getForUpdate($ids);
            $existingIds = $reviews
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->values()
                ->all();

            $lockedIds = $reviews
                ->filter(static fn (HospitalReview $review): bool => $review->isStatusChangeLocked())
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->values()
                ->all();

            if ($lockedIds !== []) {
                throw new CustomException(
                    ErrorCode::INVALID_REQUEST,
                    sprintf(
                        '자동 블라인드, 게시중단, 본인삭제 상태의 후기는 노출 상태를 변경할 수 없습니다. (ID: %s)',
                        implode(', ', $lockedIds),
                    ),
                );
            }

            $updatedCount = $this->query->update($existingIds, $status);

            foreach ($reviews as $review) {
                $beforeStatus = (string) $review->status;
                if ($beforeStatus === $status) {
                    continue;
                }

                $this->historyCreateAction->execute(
                    target: $review,
                    action: OperationHistory::ACTION_STATUS_UPDATED,
                    actor: $actor instanceof Model ? $actor : null,
                    field: 'status',
                    beforeValue: $beforeStatus,
                    afterValue: $status,
                    reason: $hiddenReason,
                    metadata: [
                        'before_label' => $beforeStatus === HospitalReview::STATUS_ACTIVE ? '노출' : '미노출',
                        'after_label' => $status === HospitalReview::STATUS_ACTIVE ? '노출' : '미노출',
                        'source' => 'staff.hospital-review.status',
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
