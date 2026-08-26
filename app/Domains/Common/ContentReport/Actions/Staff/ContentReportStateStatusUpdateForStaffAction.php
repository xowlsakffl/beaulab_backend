<?php

namespace App\Domains\Common\ContentReport\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
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

final class ContentReportStateStatusUpdateForStaffAction
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

        Gate::authorize('updateStatus', [ContentReportState::class, $targetAlias]);

        $target = ContentReportTargetRegistry::resolveTarget($targetAlias, (int) $payload['target_id']);
        $nextReportStatus = (string) $payload['report_status'];
        $this->assertProcessableStatus($nextReportStatus);
        $processReason = $this->normalizeReason($payload['process_reason'] ?? null);
        $actor = auth()->user();

        $result = DB::transaction(function () use ($target, $nextReportStatus, $processReason, $actor): array {
            $state = $this->query->getStateForUpdate($target::class, (int) $target->getKey());

            if (! $state instanceof ContentReportState || $state->report_status === ContentReportState::STATUS_NONE) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '신고 접수 내역이 없는 대상입니다.');
            }

            $previousReportStatus = (string) $state->report_status;
            $reportStatusAfter = $this->applyReportState($state, $nextReportStatus, $processReason);
            $targetChange = $this->applyTargetState($target, $nextReportStatus);

            $state->processed_by = $actor instanceof Model ? (int) $actor->getKey() : null;
            $state->save();

            $this->recordHistory(
                target: $target,
                actor: $actor instanceof Model ? $actor : null,
                reason: $processReason,
                reportStatusBefore: $previousReportStatus,
                reportStatusAfter: $reportStatusAfter,
                targetChange: $targetChange,
            );

            $state->load('processedBy:id,name,email');

            return ContentReportStateForStaffDto::fromModel($state)->toArray();
        });

        ContentReportSummaryCache::forgetForTarget($target);

        return $result;
    }

    private function assertProcessableStatus(string $nextReportStatus): void
    {
        if (in_array($nextReportStatus, ContentReportState::processableStatuses(), true)) {
            return;
        }

        throw new CustomException(ErrorCode::INVALID_REQUEST, '신고 대상에 사용할 수 없는 처리 상태입니다.');
    }

    private function applyReportState(ContentReportState $state, string $nextReportStatus, ?string $processReason): string
    {
        $previousReportStatus = (string) $state->report_status;
        $now = now();

        if ($nextReportStatus === ContentReportState::STATUS_ADMIN_HIDDEN) {
            $state->report_status = ContentReportState::STATUS_ADMIN_HIDDEN;
            $state->admin_hidden_at = $now;
            $state->process_reason = $processReason;

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

        return $reportStatusAfter;
    }

    /**
     * @return array{key:string,label:string,before:string,after:string,before_display:string,after_display:string}|null
     */
    private function applyTargetState(Model $target, string $nextReportStatus): ?array
    {
        if ($target instanceof HospitalVideo) {
            $before = (string) $target->admin_status;
            $after = $nextReportStatus === ContentReportState::STATUS_ADMIN_HIDDEN
                ? HospitalVideo::ADMIN_STATUS_FORCED_STOPPED
                : HospitalVideo::ADMIN_STATUS_NORMAL;

            if ($before !== $after) {
                $target->forceFill(['admin_status' => $after])->save();
            }

            return [
                'key' => 'admin_status',
                'label' => '강제중지',
                'before' => $before,
                'after' => $after,
                'before_display' => $this->videoReportTargetStatusDisplayLabel($before),
                'after_display' => $this->videoReportTargetStatusDisplayLabel($after),
            ];
        }

        if (! Schema::hasColumn($target->getTable(), 'status')) {
            return null;
        }

        $before = (string) $target->getAttribute('status');
        $after = $nextReportStatus === ContentReportState::STATUS_ADMIN_HIDDEN ? 'INACTIVE' : 'ACTIVE';

        if ($before !== $after) {
            $target->forceFill(['status' => $after])->save();
        }

        return [
            'key' => 'status',
            'label' => '공개여부',
            'before' => $before,
            'after' => $after,
            'before_display' => $this->visibilityLabel($before),
            'after_display' => $this->visibilityLabel($after),
        ];
    }

    /**
     * @param  array{key:string,label:string,before:string,after:string,before_display:string,after_display:string}|null  $targetChange
     */
    private function recordHistory(
        Model $target,
        ?Model $actor,
        ?string $reason,
        string $reportStatusBefore,
        string $reportStatusAfter,
        ?array $targetChange,
    ): void {
        $changesBuilder = OperationHistoryChangeSetBuilder::make()
            ->compare(
                key: 'report_status',
                label: $target instanceof HospitalVideo ? '신고상태' : '조치유형',
                before: $reportStatusBefore,
                after: $reportStatusAfter,
                beforeDisplay: $this->reportStatusDisplayLabel($target, $reportStatusBefore),
                afterDisplay: $this->reportStatusDisplayLabel($target, $reportStatusAfter),
            );

        if ($targetChange !== null) {
            $changesBuilder->compare(
                key: $targetChange['key'],
                label: $targetChange['label'],
                before: $targetChange['before'],
                after: $targetChange['after'],
                beforeDisplay: $targetChange['before_display'],
                afterDisplay: $targetChange['after_display'],
            );
        }

        $changes = $changesBuilder->toArray();
        if ($changes === []) {
            return;
        }

        $this->historyCreateAction->execute(
            target: $target,
            action: OperationHistory::ACTION_STATE_UPDATED,
            actor: $actor,
            reason: $reason,
            metadata: [
                'report_status_before' => $reportStatusBefore,
                'report_status_after' => $reportStatusAfter,
                'source' => $reportStatusAfter === ContentReportState::STATUS_REEXPOSED
                    ? 'staff.content_report.reexposed'
                    : 'staff.content_report.status',
            ],
            changes: $changes,
        );
    }

    private function visibilityLabel(string $status): string
    {
        return $status === 'ACTIVE' ? '노출' : '미노출';
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

    private function normalizeReason(mixed $reason): ?string
    {
        $reason = trim((string) $reason);

        return $reason === '' ? null : $reason;
    }
}
