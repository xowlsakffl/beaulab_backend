<?php

namespace App\Domains\HospitalReview\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\HospitalReview\Queries\Staff\HospitalReviewCommentStatusUpdateForStaffQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalReviewCommentStatusUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalReviewCommentStatusUpdateForStaffQuery $query,
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('update', HospitalReviewComment::class);

        $ids = collect($payload['ids'] ?? [])
            ->map(static fn (int|string $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
        $status = (string) $payload['status'];
        $historyReason = $status === HospitalReviewComment::STATUS_ACTIVE
            ? null
            : $this->normalizeReason($payload['hidden_reason'] ?? null);
        $actor = auth()->user();

        return DB::transaction(function () use ($ids, $status, $historyReason, $actor): array {
            $comments = $this->query->getForUpdate($ids);
            $existingIds = $comments
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->values()
                ->all();

            $updatedCount = $this->query->update($existingIds, $status);

            foreach ($comments as $comment) {
                $beforeStatus = (string) $comment->status;
                if ($beforeStatus === $status) {
                    continue;
                }

                $this->historyCreateAction->execute(
                    target: $comment,
                    action: OperationHistory::ACTION_STATUS_UPDATED,
                    actor: $actor instanceof Model ? $actor : null,
                    field: 'status',
                    beforeValue: $beforeStatus,
                    afterValue: $status,
                    reason: $historyReason,
                    metadata: [
                        'before_label' => $beforeStatus === HospitalReviewComment::STATUS_ACTIVE ? '노출' : '미노출',
                        'after_label' => $status === HospitalReviewComment::STATUS_ACTIVE ? '노출' : '미노출',
                        'source' => 'staff.hospital_review_comment.status',
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
