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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

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

        Gate::authorize('update', $targetClass);

        $target = ContentReportTargetRegistry::resolveTarget($targetAlias, (int) $payload['target_id']);
        $nextReportStatus = (string) $payload['report_status'];
        $processReason = $this->normalizeReason($payload['process_reason'] ?? null);
        $actor = auth()->user();

        $result = DB::transaction(function () use ($target, $nextReportStatus, $processReason, $actor): array {
            $state = $this->query->getStateForUpdate($target::class, (int) $target->getKey());

            if (! $state instanceof ContentReportState || $state->report_status === ContentReportState::STATUS_NONE) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '신고 접수 내역이 없는 대상입니다.');
            }

            $previousReportStatus = (string) $state->report_status;
            $now = now();

            if ($nextReportStatus === ContentReportState::STATUS_ADMIN_HIDDEN) {
                $state->report_status = ContentReportState::STATUS_ADMIN_HIDDEN;
                $state->admin_hidden_at = $now;
                $state->process_reason = $processReason;
                $this->applyTargetStatus(
                    target: $target,
                    status: 'INACTIVE',
                    reason: $this->adminHiddenHistoryReason($processReason),
                    reportStatusBefore: $previousReportStatus,
                    reportStatusAfter: ContentReportState::STATUS_ADMIN_HIDDEN,
                    actor: $actor instanceof Model ? $actor : null,
                    source: 'staff.content_report.admin_hidden',
                );
            }

            if ($nextReportStatus === ContentReportState::STATUS_NORMAL_VISIBLE) {
                if (in_array($previousReportStatus, [
                    ContentReportState::STATUS_AUTO_BLOCKED,
                    ContentReportState::STATUS_ADMIN_HIDDEN,
                ], true)) {
                    $state->normal_visible_count = (int) $state->normal_visible_count + 1;
                }

                $state->report_status = ContentReportState::STATUS_NORMAL_VISIBLE;
                $state->normal_visible_at = $now;
                $state->process_reason = $processReason;
                $this->applyTargetStatus(
                    target: $target,
                    status: 'ACTIVE',
                    reason: $processReason ?? '신고 정상처리 정상노출',
                    reportStatusBefore: $previousReportStatus,
                    reportStatusAfter: ContentReportState::STATUS_NORMAL_VISIBLE,
                    actor: $actor instanceof Model ? $actor : null,
                    source: 'staff.content_report.normal_visible',
                );
            }

            $state->processed_by = $actor instanceof Model ? (int) $actor->getKey() : null;
            $state->save();
            $state->load('processedBy:id,name,email');

            return ContentReportStateForStaffDto::fromModel($state)->toArray();
        });

        ContentReportSummaryCache::forgetForTarget($target);

        return $result;
    }

    private function normalizeReason(mixed $reason): ?string
    {
        $reason = trim((string) $reason);

        return $reason === '' ? null : $reason;
    }

    private function adminHiddenHistoryReason(?string $processReason): string
    {
        if ($processReason === null) {
            return '신고 처리 노출중지';
        }

        return "신고 처리 노출중지 - {$processReason}";
    }

    private function applyTargetStatus(
        Model $target,
        string $status,
        string $reason,
        string $reportStatusBefore,
        string $reportStatusAfter,
        ?Model $actor,
        string $source,
    ): void {
        $beforeStatus = (string) $target->getAttribute('status');

        if ($beforeStatus !== $status) {
            $target->forceFill(['status' => $status])->save();
        }

        $this->historyCreateAction->execute(
            target: $target,
            action: OperationHistory::ACTION_STATUS_UPDATED,
            actor: $actor,
            field: 'status',
            beforeValue: $beforeStatus,
            afterValue: $status,
            reason: $reason,
            metadata: [
                'before_label' => $beforeStatus === 'ACTIVE' ? '노출' : '미노출',
                'after_label' => $status === 'ACTIVE' ? '노출' : '미노출',
                'report_status_before' => $reportStatusBefore,
                'report_status_after' => $reportStatusAfter,
                'source' => $source,
            ],
        );
    }
}
