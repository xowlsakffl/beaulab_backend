<?php

namespace App\Domains\Talk\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
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
        $historyReason = $status === TalkComment::STATUS_ACTIVE ? null : $this->normalizeReason($payload['hidden_reason'] ?? null);
        $actor = auth()->user();

        return DB::transaction(function () use ($ids, $status, $historyReason, $actor): array {
            $comments = $this->query->getForUpdate($ids);
            if ($comments->contains(fn (TalkComment $comment): bool => $comment->isStatusChangeLocked())) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '신고 처리 상태가 자동차단 또는 노출중지인 댓글은 노출여부를 변경할 수 없습니다.');
            }

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
                    reason: $historyReason,
                    metadata: [
                        'source' => 'staff.talk_comment.status',
                        'bulk' => count($existingIds) > 1,
                    ],
                    changes: OperationHistoryChangeSetBuilder::single(
                        key: 'status',
                        label: '노출여부',
                        before: $beforeStatus,
                        after: $status,
                        beforeDisplay: $beforeStatus === TalkComment::STATUS_ACTIVE ? '노출' : '미노출',
                        afterDisplay: $status === TalkComment::STATUS_ACTIVE ? '노출' : '미노출',
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
