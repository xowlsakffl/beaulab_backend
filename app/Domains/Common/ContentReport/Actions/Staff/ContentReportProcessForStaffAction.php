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

final class ContentReportProcessForStaffAction
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

        Gate::authorize('update', [ContentReportState::class, $targetAlias]);

        $target = ContentReportTargetRegistry::resolveTarget($targetAlias, (int) $payload['target_id']);
        $nextReportStatus = (string) $payload['report_status'];
        $this->assertProcessableStatus($target, $nextReportStatus);

        $processReason = $this->normalizeReason($payload['process_reason'] ?? null);
        $warningStatus = isset($payload['warning_status']) ? (string) $payload['warning_status'] : null;
        $actor = auth()->user();

        $result = DB::transaction(function () use ($target, $nextReportStatus, $processReason, $warningStatus, $actor): array {
            $state = $this->query->getStateForUpdate($target::class, (int) $target->getKey());

            if (! $state instanceof ContentReportState || $state->report_status === ContentReportState::STATUS_NONE) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '신고 접수 내역이 없는 대상입니다.');
            }

            $previousReportStatus = (string) $state->report_status;
            $beforeWarningStatus = (string) $state->warning_status;
            $targetStatusBefore = $this->hasStatusColumn($target) ? (string) $target->getAttribute('status') : null;
            $now = now();
            $reportStatusAfter = $this->applyReportStatus($target, $state, $nextReportStatus, $processReason, $now);

            $metadata = [
                'report_status_before' => $previousReportStatus,
                'report_status_after' => $reportStatusAfter,
                'source' => 'staff.content_report.process',
            ];
            $changesBuilder = $this->buildReportStatusChanges($target, $previousReportStatus, $reportStatusAfter, $targetStatusBefore);

            if ($warningStatus !== null && $warningStatus !== '') {
                $this->applyWarningStatus($target, $state, $warningStatus, $reportStatusAfter, $metadata, $changesBuilder);
            }

            $state->processed_by = $actor instanceof Model ? (int) $actor->getKey() : null;
            $state->save();

            $this->historyCreateAction->execute(
                target: $target,
                action: OperationHistory::ACTION_STATE_UPDATED,
                actor: $actor instanceof Model ? $actor : null,
                reason: $processReason,
                metadata: $metadata,
                changes: $changesBuilder->toArray(),
            );

            $state->load(['processedBy:id,name,email', 'warningProcessedBy:id,name,email']);

            return ContentReportStateForStaffDto::fromModel($state)->toArray();
        });

        ContentReportSummaryCache::forgetForTarget($target);

        return $result;
    }

    private function applyReportStatus(
        Model $target,
        ContentReportState $state,
        string $nextReportStatus,
        ?string $processReason,
        mixed $now,
    ): string {
        $previousReportStatus = (string) $state->report_status;

        if ($nextReportStatus === ContentReportState::STATUS_ADMIN_HIDDEN) {
            $state->report_status = ContentReportState::STATUS_ADMIN_HIDDEN;
            $state->admin_hidden_at = $now;
            $state->process_reason = $processReason;
            $this->applyTargetVisibility($target, 'INACTIVE');

            return ContentReportState::STATUS_ADMIN_HIDDEN;
        }

        if (! in_array($previousReportStatus, [
            ContentReportState::STATUS_NORMAL_VISIBLE,
            ContentReportState::STATUS_REEXPOSED,
        ], true)) {
            $state->normal_visible_count = (int) $state->normal_visible_count + 1;
        }

        $reportStatusAfter = (int) $state->normal_visible_count >= ContentReportState::AUTO_ACTION_LOCK_NORMAL_VISIBLE_THRESHOLD
            ? ContentReportState::STATUS_REEXPOSED
            : ContentReportState::STATUS_NORMAL_VISIBLE;

        $state->report_status = $reportStatusAfter;
        $state->normal_visible_at = $now;
        $state->recent_hour_report_count = 0;
        $state->process_reason = $processReason;
        $this->applyTargetVisibility($target, 'ACTIVE');

        return $reportStatusAfter;
    }

    private function applyWarningStatus(
        Model $target,
        ContentReportState $state,
        string $warningStatus,
        string $reportStatusAfter,
        array &$metadata,
        OperationHistoryChangeSetBuilder $changesBuilder,
    ): void {
        if (! $this->canProcessWarning($target, $reportStatusAfter)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '경고/무시 처리가 가능한 상태에서만 선택할 수 있습니다.');
        }

        $authorId = $this->targetAuthorId($target);
        if ($authorId === null) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '작성자 정보가 없는 신고 대상입니다.');
        }

        $beforeWarningStatus = (string) $state->warning_status;
        $this->assertWarningTransitionAllowed($beforeWarningStatus, $warningStatus);

        $metadata['warning_status_before'] = $beforeWarningStatus;
        $metadata['warning_status_after'] = $warningStatus;
        $this->applyWarningTransition($authorId, $beforeWarningStatus, $warningStatus, $metadata);

        $state->warning_status = $warningStatus;
        $state->warning_processed_at = now();
        $actor = auth()->user();
        $state->warning_processed_by = $actor instanceof Model ? (int) $actor->getKey() : null;

        $changesBuilder->compare(
            key: 'warning_status',
            label: '경고여부 변경',
            before: $beforeWarningStatus,
            after: $warningStatus,
            beforeDisplay: ContentReportState::warningStatusLabels()[$beforeWarningStatus] ?? $beforeWarningStatus,
            afterDisplay: ContentReportState::warningStatusLabels()[$warningStatus] ?? $warningStatus,
        );
    }

    private function buildReportStatusChanges(
        Model $target,
        string $reportStatusBefore,
        string $reportStatusAfter,
        ?string $targetStatusBefore,
    ): OperationHistoryChangeSetBuilder {
        $changesBuilder = OperationHistoryChangeSetBuilder::make()
            ->compare(
                key: 'report_status',
                label: '조치유형 변경',
                before: $reportStatusBefore,
                after: $reportStatusAfter,
                beforeDisplay: ContentReportState::statusLabels()[$reportStatusBefore] ?? $reportStatusBefore,
                afterDisplay: ContentReportState::statusLabels()[$reportStatusAfter] ?? $reportStatusAfter,
            );

        if ($targetStatusBefore !== null && $this->hasStatusColumn($target)) {
            $afterStatus = (string) $target->getAttribute('status');

            if ($targetStatusBefore !== $afterStatus) {
                $changesBuilder->compare(
                    key: 'status',
                    label: '노출여부 변경',
                    before: $targetStatusBefore,
                    after: $afterStatus,
                    beforeDisplay: $targetStatusBefore === 'ACTIVE' ? '노출' : '미노출',
                    afterDisplay: $afterStatus === 'ACTIVE' ? '노출' : '미노출',
                );
            }
        }

        return $changesBuilder;
    }

    private function assertProcessableStatus(Model $target, string $nextReportStatus): void
    {
        if (
            $this->hasStatusColumn($target)
            && in_array($nextReportStatus, [
                ContentReportState::STATUS_ADMIN_HIDDEN,
                ContentReportState::STATUS_NORMAL_VISIBLE,
            ], true)
        ) {
            return;
        }

        throw new CustomException(ErrorCode::INVALID_REQUEST, '신고 대상에 사용할 수 없는 처리 상태입니다.');
    }

    private function applyTargetVisibility(Model $target, string $status): void
    {
        if (! $this->hasStatusColumn($target)) {
            return;
        }

        if ((string) $target->getAttribute('status') === $status) {
            return;
        }

        $target->forceFill(['status' => $status])->save();
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

    private function assertWarningTransitionAllowed(string $beforeWarningStatus, string $warningStatus): void
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

    private function normalizeReason(mixed $reason): ?string
    {
        $reason = trim((string) $reason);

        return $reason === '' ? null : $reason;
    }

    private function hasStatusColumn(Model $target): bool
    {
        return Schema::hasColumn($target->getTable(), 'status');
    }
}
