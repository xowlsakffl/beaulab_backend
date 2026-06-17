<?php

namespace App\Domains\Common\ContentReport\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Common\ContentReport\Dto\Staff\ContentReportStateForStaffDto;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Common\ContentReport\Queries\Staff\ContentReportStateStatusUpdateForStaffQuery;
use App\Domains\Common\ContentReport\Support\ContentReportSummaryCache;
use App\Domains\Common\ContentReport\Support\ContentReportTargetRegistry;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

final class ContentReportWarningStatusUpdateForStaffAction
{
    public function __construct(
        private readonly ContentReportStateStatusUpdateForStaffQuery $query,
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    public function execute(array $payload): array
    {
        $targetAlias = (string) $payload['target_type'];
        $targetClass = ContentReportTargetRegistry::classForAlias($targetAlias);

        if ($targetClass === null) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '지원하지 않는 신고 대상입니다.');
        }

        Gate::authorize('update', $targetClass);

        $target = ContentReportTargetRegistry::resolveTarget($targetAlias, (int) $payload['target_id']);
        $warningStatus = (string) $payload['warning_status'];
        $actor = auth()->user();

        $result = DB::transaction(function () use ($target, $warningStatus, $actor): array {
            $state = $this->query->getStateForUpdate($target::class, (int) $target->getKey());

            if (! $state instanceof ContentReportState || $state->report_status === ContentReportState::STATUS_NONE) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '신고 접수 내역이 없는 대상입니다.');
            }

            if (! $this->canProcessWarning($target, (string) $state->report_status)) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '경고/무시 처리가 가능한 상태에서만 선택할 수 있습니다.');
            }

            $authorId = $this->targetAuthorId($target);
            if ($authorId === null) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '작성자 정보가 없는 신고 대상입니다.');
            }

            $beforeWarningStatus = (string) $state->warning_status;
            $this->assertTransitionAllowed($beforeWarningStatus, $warningStatus);

            $metadata = [
                'before_label' => ContentReportState::warningStatusLabels()[$beforeWarningStatus] ?? $beforeWarningStatus,
                'after_label' => ContentReportState::warningStatusLabels()[$warningStatus] ?? $warningStatus,
                'report_status' => (string) $state->report_status,
                'source' => 'staff.content_report.warning_status',
            ];

            $this->applyWarningTransition($authorId, $beforeWarningStatus, $warningStatus, $metadata);

            $state->warning_status = $warningStatus;
            $state->warning_processed_at = now();
            $state->warning_processed_by = $actor instanceof Model ? (int) $actor->getKey() : null;
            $state->save();

            $this->historyCreateAction->execute(
                target: $target,
                action: OperationHistory::ACTION_STATUS_UPDATED,
                actor: $actor instanceof Model ? $actor : null,
                reason: null,
                metadata: $metadata,
                changes: OperationHistoryChangeSetBuilder::single(
                    key: 'warning_status',
                    label: '경고여부',
                    before: $beforeWarningStatus,
                    after: $warningStatus,
                    beforeDisplay: $metadata['before_label'],
                    afterDisplay: $metadata['after_label'],
                ),
            );

            $state->load(['processedBy:id,name,email', 'warningProcessedBy:id,name,email']);

            return ContentReportStateForStaffDto::fromModel($state)->toArray();
        });

        ContentReportSummaryCache::forgetForTarget($target);

        return $result;
    }

    private function targetAuthorId(Model $target): ?int
    {
        $authorId = $target->getAttribute('author_id') ?? $target->getAttribute('sender_user_id');

        return $authorId === null ? null : (int) $authorId;
    }

    private function canProcessWarning(Model $target, string $reportStatus): bool
    {
        if ($target instanceof ChatMessage) {
            return $reportStatus === ContentReportState::STATUS_VALID;
        }

        if (Schema::hasColumn($target->getTable(), 'status')) {
            return $reportStatus === ContentReportState::STATUS_ADMIN_HIDDEN;
        }

        return in_array($reportStatus, [
            ContentReportState::STATUS_INVALID,
            ContentReportState::STATUS_NORMAL_VISIBLE,
        ], true);
    }

    private function assertTransitionAllowed(string $beforeWarningStatus, string $warningStatus): void
    {
        if ($beforeWarningStatus === $warningStatus) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '이미 동일한 경고 상태입니다.');
        }

        if (
            $beforeWarningStatus === ContentReportState::WARNING_STATUS_WARNED
            && $warningStatus !== ContentReportState::WARNING_STATUS_IGNORED
        ) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '경고 처리된 신고는 무시로만 변경할 수 있습니다.');
        }
    }

    private function applyWarningTransition(
        int $authorId,
        string $beforeWarningStatus,
        string $warningStatus,
        array &$metadata,
    ): void {
        if ($warningStatus === ContentReportState::WARNING_STATUS_WARNED) {
            $this->applyUserWarning($authorId, $metadata);

            return;
        }

        if ($beforeWarningStatus === ContentReportState::WARNING_STATUS_WARNED) {
            $this->removeUserWarning($authorId, $metadata);

            return;
        }
    }

    private function applyUserWarning(int $authorId, array &$metadata): void
    {
        $author = AccountUser::query()
            ->whereKey($authorId)
            ->lockForUpdate()
            ->first();

        if (! $author instanceof AccountUser) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '작성자 정보를 찾을 수 없습니다.');
        }

        $beforeWarningCount = (int) $author->warning_count;
        $afterWarningCount = $beforeWarningCount + 1;
        $beforeAccountStatus = (string) $author->status;
        $accountBlocked = false;

        $author->warning_count = $afterWarningCount;

        if (
            $afterWarningCount >= AccountUser::WARNING_BLOCK_THRESHOLD
            && $author->status !== AccountUser::STATUS_BLOCKED
            && $author->status !== AccountUser::STATUS_WITHDRAWN
        ) {
            $author->status = AccountUser::STATUS_BLOCKED;
            $author->blocked_at ??= now();
            $accountBlocked = true;
        }

        $author->save();

        $metadata['account_user_id'] = (int) $author->id;
        $metadata['warning_count_before'] = $beforeWarningCount;
        $metadata['warning_count_after'] = $afterWarningCount;
        $metadata['account_status_before'] = $beforeAccountStatus;
        $metadata['account_status_after'] = (string) $author->status;
        $metadata['account_blocked'] = $accountBlocked;
    }

    private function removeUserWarning(int $authorId, array &$metadata): void
    {
        $author = AccountUser::query()
            ->whereKey($authorId)
            ->lockForUpdate()
            ->first();

        if (! $author instanceof AccountUser) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '작성자 정보를 찾을 수 없습니다.');
        }

        $beforeWarningCount = (int) $author->warning_count;
        $afterWarningCount = max(0, $beforeWarningCount - 1);
        $beforeAccountStatus = (string) $author->status;
        $accountUnblocked = false;

        $author->warning_count = $afterWarningCount;

        if (
            $beforeAccountStatus === AccountUser::STATUS_BLOCKED
            && $beforeWarningCount >= AccountUser::WARNING_BLOCK_THRESHOLD
            && $afterWarningCount < AccountUser::WARNING_BLOCK_THRESHOLD
        ) {
            $author->status = AccountUser::STATUS_ACTIVE;
            $author->blocked_at = null;
            $accountUnblocked = true;
        }

        $author->save();

        $metadata['account_user_id'] = (int) $author->id;
        $metadata['warning_count_before'] = $beforeWarningCount;
        $metadata['warning_count_after'] = $afterWarningCount;
        $metadata['account_status_before'] = $beforeAccountStatus;
        $metadata['account_status_after'] = (string) $author->status;
        $metadata['account_unblocked'] = $accountUnblocked;
    }
}
