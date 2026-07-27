<?php

namespace App\Domains\Common\ContentReport\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Cache\Support\StaffSummaryCache;
use App\Domains\Common\ContentReport\Dto\Staff\ContentReportStateForStaffDto;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Common\ContentReport\Queries\Staff\ContentReportStateStatusUpdateForStaffQuery;
use App\Domains\Common\ContentReport\Support\ContentReportSummaryCache;
use App\Domains\Common\ContentReport\Support\ContentReportTargetRegistry;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\HospitalVideo\Models\HospitalVideo;
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
        $this->assertProcessableStatus($nextReportStatus);

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
            $targetStateBefore = $this->targetStateSnapshot($target);
            $reportStatusAfter = $this->applyReportStatus($target, $state, $nextReportStatus, $processReason);

            $metadata = [
                'report_status_before' => $previousReportStatus,
                'report_status_after' => $reportStatusAfter,
                'source' => 'staff.content_report.process',
            ];
            $changesBuilder = $this->buildReportStatusChanges($target, $previousReportStatus, $reportStatusAfter, $targetStateBefore);

            if ($warningStatus !== null && $warningStatus !== '') {
                $this->applyWarningStatus($target, $state, $warningStatus, $reportStatusAfter, $metadata, $changesBuilder);
            }

            $state->processed_by = $actor instanceof Model ? (int) $actor->getKey() : null;
            $state->save();

            $changes = $changesBuilder->toArray();
            if ($changes !== []) {
                $this->historyCreateAction->execute(
                    target: $target,
                    action: OperationHistory::ACTION_STATE_UPDATED,
                    actor: $actor instanceof Model ? $actor : null,
                    reason: $processReason,
                    metadata: [
                        ...$metadata,
                        'warning_status_before' => $metadata['warning_status_before'] ?? $beforeWarningStatus,
                        'warning_status_after' => $metadata['warning_status_after'] ?? (string) $state->warning_status,
                    ],
                    changes: $changes,
                );
            }

            $state->load(['processedBy:id,name,email', 'warningProcessedBy:id,name,email']);

            return ContentReportStateForStaffDto::fromModel($state)->toArray();
        });

        ContentReportSummaryCache::forgetForTarget($target);
        StaffSummaryCache::forget(StaffSummaryCache::DOMAIN_ACCOUNT_USER);

        return $result;
    }

    private function applyReportStatus(
        Model $target,
        ContentReportState $state,
        string $nextReportStatus,
        ?string $processReason,
    ): string {
        $previousReportStatus = (string) $state->report_status;
        $now = now();

        if ($nextReportStatus === ContentReportState::STATUS_ADMIN_HIDDEN) {
            $state->report_status = ContentReportState::STATUS_ADMIN_HIDDEN;
            $state->admin_hidden_at = $now;
            $state->process_reason = $processReason;
            $this->applyTargetVisibility($target, ContentReportState::STATUS_ADMIN_HIDDEN);

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
        $this->applyTargetVisibility($target, ContentReportState::STATUS_NORMAL_VISIBLE);

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
            label: '경고여부',
            before: $beforeWarningStatus,
            after: $warningStatus,
            beforeDisplay: ContentReportState::warningStatusLabels()[$beforeWarningStatus] ?? $beforeWarningStatus,
            afterDisplay: ContentReportState::warningStatusLabels()[$warningStatus] ?? $warningStatus,
        );
    }

    /**
     * @param  array{key:string,label:string,value:string,display:string}|null  $targetStateBefore
     */
    private function buildReportStatusChanges(
        Model $target,
        string $reportStatusBefore,
        string $reportStatusAfter,
        ?array $targetStateBefore,
    ): OperationHistoryChangeSetBuilder {
        $changesBuilder = OperationHistoryChangeSetBuilder::make()
            ->compare(
                key: 'report_status',
                label: $target instanceof HospitalVideo ? '신고상태' : '조치유형',
                before: $reportStatusBefore,
                after: $reportStatusAfter,
                beforeDisplay: $this->reportStatusDisplayLabel($target, $reportStatusBefore),
                afterDisplay: $this->reportStatusDisplayLabel($target, $reportStatusAfter),
            );

        if ($targetStateBefore !== null) {
            $targetStateAfter = $this->targetStateSnapshot($target);

            if ($targetStateAfter !== null && $targetStateBefore['value'] !== $targetStateAfter['value']) {
                $changesBuilder->compare(
                    key: $targetStateBefore['key'],
                    label: $targetStateBefore['label'],
                    before: $targetStateBefore['value'],
                    after: $targetStateAfter['value'],
                    beforeDisplay: $targetStateBefore['display'],
                    afterDisplay: $targetStateAfter['display'],
                );
            }
        }

        return $changesBuilder;
    }

    private function assertProcessableStatus(string $nextReportStatus): void
    {
        if (in_array($nextReportStatus, ContentReportState::processableStatuses(), true)) {
            return;
        }

        throw new CustomException(ErrorCode::INVALID_REQUEST, '신고 대상에 사용할 수 없는 처리 상태입니다.');
    }

    private function applyTargetVisibility(Model $target, string $reportStatus): void
    {
        if ($target instanceof HospitalVideo) {
            $adminStatus = $reportStatus === ContentReportState::STATUS_ADMIN_HIDDEN
                ? HospitalVideo::ADMIN_STATUS_FORCED_STOPPED
                : HospitalVideo::ADMIN_STATUS_NORMAL;

            if ((string) $target->admin_status !== $adminStatus) {
                $target->forceFill(['admin_status' => $adminStatus])->save();
            }

            return;
        }

        if (! $this->hasStatusColumn($target)) {
            return;
        }

        $status = $reportStatus === ContentReportState::STATUS_ADMIN_HIDDEN ? 'INACTIVE' : 'ACTIVE';

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
        if ($target instanceof HospitalVideo) {
            return false;
        }

        return $reportStatus === ContentReportState::STATUS_ADMIN_HIDDEN;
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
            throw new CustomException(ErrorCode::INVALID_REQUEST, '경고 처리는 신고 무시로만 변경할 수 있습니다.');
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

    /**
     * @return array{key:string,label:string,value:string,display:string}|null
     */
    private function targetStateSnapshot(Model $target): ?array
    {
        if ($target instanceof HospitalVideo) {
            $value = (string) $target->admin_status;

            return [
                'key' => 'admin_status',
                'label' => '강제중지',
                'value' => $value,
                'display' => $this->videoReportTargetStatusDisplayLabel($value),
            ];
        }

        if (! $this->hasStatusColumn($target)) {
            return null;
        }

        $value = (string) $target->getAttribute('status');

        return [
            'key' => 'status',
            'label' => '노출여부',
            'value' => $value,
            'display' => $value === 'ACTIVE' ? '노출' : '미노출',
        ];
    }

    private function reportStatusDisplayLabel(Model $target, string $status): string
    {
        if ($target instanceof HospitalVideo) {
            return match ($status) {
                ContentReportState::STATUS_NONE => '-',
                ContentReportState::STATUS_REPORTED,
                ContentReportState::STATUS_AUTO_BLOCKED => '신고접수',
                ContentReportState::STATUS_ADMIN_HIDDEN => '삭제처리',
                ContentReportState::STATUS_NORMAL_VISIBLE,
                ContentReportState::STATUS_REEXPOSED => '신고오류',
                default => $status,
            };
        }

        return ContentReportState::statusLabels()[$status] ?? $status;
    }

    private function videoReportTargetStatusDisplayLabel(string $status): string
    {
        return match ($status) {
            HospitalVideo::ADMIN_STATUS_NORMAL => '정상',
            HospitalVideo::ADMIN_STATUS_FORCED_STOPPED => '강제중지',
            default => $status,
        };
    }
}
