<?php

namespace App\Domains\Talk\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Talk\Models\TalkComment;
use App\Domains\Talk\Queries\Staff\TalkCommentStatusUpdateForStaffQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * 토크 댓글 다중 상태 변경 유스케이스.
 */
final class TalkCommentStatusUpdateForStaffAction
{
    public function __construct(
        private readonly TalkCommentStatusUpdateForStaffQuery $query,
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('update', TalkComment::class);

        $ids = collect($payload['ids'] ?? [])
            ->map(static fn (int|string $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
        $status = (string) $payload['status'];
        $hiddenReason = $status === TalkComment::STATUS_ACTIVE ? null : $this->normalizeReason($payload['hidden_reason'] ?? null);
        $actor = auth()->user();

        return DB::transaction(function () use ($ids, $status, $hiddenReason, $actor): array {
            $comments = $this->query->getForUpdate($ids);
            $existingIds = $comments
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->values()
                ->all();

            $lockedIds = $comments
                ->filter(static fn (TalkComment $comment): bool => $comment->isStatusChangeLocked())
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->values()
                ->all();

            if ($lockedIds !== []) {
                throw new CustomException(
                    ErrorCode::INVALID_REQUEST,
                    sprintf(
                        '자동 블라인드, 게시중단, 본인삭제 상태의 토크 댓글은 상태를 변경할 수 없습니다. (ID: %s)',
                        implode(', ', $lockedIds),
                    ),
                );
            }

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
                    reason: $hiddenReason,
                    metadata: [
                        'before_label' => $beforeStatus === TalkComment::STATUS_ACTIVE ? '노출' : '미노출',
                        'after_label' => $status === TalkComment::STATUS_ACTIVE ? '노출' : '미노출',
                        'source' => 'staff.talk_comment.status',
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
