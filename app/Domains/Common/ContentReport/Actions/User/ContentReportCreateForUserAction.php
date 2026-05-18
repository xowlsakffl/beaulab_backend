<?php

namespace App\Domains\Common\ContentReport\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\ContentReport\Models\ContentReport;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Common\ContentReport\Queries\User\ContentReportCreateForUserQuery;
use App\Domains\Common\ContentReport\Support\ContentReportSummaryCache;
use App\Domains\Common\ContentReport\Support\ContentReportTargetRegistry;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ContentReportCreateForUserAction
{
    public function __construct(
        private readonly ContentReportCreateForUserQuery $query,
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    public function execute(Model $reporter, Model $target, array $payload): array
    {
        ContentReportTargetRegistry::assertSupported($target);

        $reporterUserId = (int) $reporter->getKey();
        $targetType = $target::class;
        $targetId = (int) $target->getKey();

        $result = DB::transaction(function () use ($reporterUserId, $targetType, $targetId, $target, $payload): array {
            // TODO: 테스트 기간에는 동일 유저가 같은 콘텐츠를 여러 번 신고할 수 있게 허용한다.
            // 운영 정책 확정 시 아래 중복 신고 제한과 DB unique index를 같이 복구해야 한다.
            // if ($this->query->findExistingReport($reporterUserId, $targetType, $targetId) instanceof ContentReport) {
            //     throw new CustomException(ErrorCode::INVALID_REQUEST, '이미 신고한 콘텐츠입니다.');
            // }

            try {
                $report = $this->query->createReport([
                    'reporter_user_id' => $reporterUserId,
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                    'target_author_id' => $this->targetAuthorId($target),
                    'reason' => (string) $payload['reason'],
                    'reason_text' => (string) $payload['reason'] === ContentReport::REASON_OTHER
                        ? $this->normalizeReasonText($payload['reason_text'] ?? null)
                        : null,
                    'content_snapshot' => $this->contentSnapshot($target, $payload),
                    'metadata' => $payload['metadata'] ?? null,
                    'reporter_ip' => $this->normalizeIp($payload['reporter_ip'] ?? null),
                ]);
            } catch (QueryException $exception) {
                if ((string) $exception->getCode() !== '23000') {
                    throw $exception;
                }

                throw new CustomException(ErrorCode::INVALID_REQUEST, '이미 신고한 콘텐츠입니다.');
            }

            $state = $this->query->getStateForUpdate($targetType, $targetId);
            if (! $state instanceof ContentReportState) {
                try {
                    $state = $this->query->createState($targetType, $targetId);
                } catch (QueryException $exception) {
                    if ((string) $exception->getCode() !== '23000') {
                        throw $exception;
                    }

                    $state = $this->query->getStateForUpdate($targetType, $targetId);
                }
            }

            if (! $state instanceof ContentReportState) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '신고 상태를 생성할 수 없습니다.');
            }

            $state->refresh();

            $now = now();
            $previousReportStatus = (string) $state->report_status;
            $reportCount = $this->query->countReports($targetType, $targetId);
            $recentHourReportCount = $this->query->countReportsSince(
                $targetType,
                $targetId,
                $this->autoBlockCountStartAt($state, $now),
            );

            $state->report_count = $reportCount;
            $state->recent_hour_report_count = $recentHourReportCount;
            $state->first_reported_at ??= $now;
            $state->last_reported_at = $now;

            if (! $state->isAutoActionLocked() && $previousReportStatus !== ContentReportState::STATUS_ADMIN_HIDDEN) {
                if (
                    $this->supportsTargetVisibilityStatus($target)
                    && $recentHourReportCount >= ContentReportState::AUTO_BLOCK_RECENT_HOUR_THRESHOLD
                ) {
                    $state->report_status = ContentReportState::STATUS_AUTO_BLOCKED;
                    $state->auto_blocked_at = $now;

                    if ($previousReportStatus !== ContentReportState::STATUS_AUTO_BLOCKED) {
                        $this->applyTargetStatus(
                            target: $target,
                            status: 'INACTIVE',
                            reason: '신고 누적 자동차단',
                            reportStatusBefore: $previousReportStatus,
                            reportStatusAfter: ContentReportState::STATUS_AUTO_BLOCKED,
                            source: 'user.content_report.auto_block',
                        );
                    }
                } elseif (in_array($previousReportStatus, [
                    ContentReportState::STATUS_NONE,
                    ContentReportState::STATUS_NORMAL_VISIBLE,
                ], true)) {
                    $state->report_status = ContentReportState::STATUS_REPORTED;
                }
            }

            $state->save();

            return [
                'report' => [
                    'id' => (int) $report->id,
                    'reason' => (string) $report->reason,
                    'reason_label' => $report->reasonLabel(),
                    'reason_text' => $report->reason_text,
                    'created_at' => $report->created_at?->toISOString(),
                ],
                'report_state' => [
                    'status' => (string) $state->report_status,
                    'label' => $state->statusLabel(),
                    'report_count' => (int) $state->report_count,
                    'recent_hour_report_count' => (int) $state->recent_hour_report_count,
                    'is_auto_action_locked' => $state->isAutoActionLocked(),
                ],
            ];
        });

        ContentReportSummaryCache::forgetForTarget($target);

        return $result;
    }

    private function autoBlockCountStartAt(ContentReportState $state, CarbonInterface $now): CarbonInterface
    {
        $hourStartAt = $now->copy()->subHour();

        if ($state->normal_visible_at !== null && $state->normal_visible_at->greaterThan($hourStartAt)) {
            return $state->normal_visible_at;
        }

        return $hourStartAt;
    }

    private function targetAuthorId(Model $target): ?int
    {
        $authorId = $target->getAttribute('author_id') ?? $target->getAttribute('sender_user_id');

        return $authorId === null ? null : (int) $authorId;
    }

    private function normalizeReasonText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeIp(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function contentSnapshot(Model $target, array $payload): ?string
    {
        if (array_key_exists('content_snapshot', $payload)) {
            $snapshot = trim((string) $payload['content_snapshot']);

            return $snapshot === '' ? null : mb_substr($snapshot, 0, 2000);
        }

        $parts = [];

        foreach (['title', 'content', 'body'] as $attribute) {
            $value = $target->getAttribute($attribute);
            if (is_string($value) && trim($value) !== '') {
                $parts[] = trim($value);
            }
        }

        if ($parts === []) {
            return null;
        }

        return mb_substr(implode("\n", $parts), 0, 2000);
    }

    private function supportsTargetVisibilityStatus(Model $target): bool
    {
        return Schema::hasColumn($target->getTable(), 'status');
    }

    private function applyTargetStatus(
        Model $target,
        string $status,
        string $reason,
        string $reportStatusBefore,
        string $reportStatusAfter,
        string $source,
    ): void {
        if (! $this->supportsTargetVisibilityStatus($target)) {
            return;
        }

        $beforeStatus = (string) $target->getAttribute('status');

        if ($beforeStatus !== $status) {
            $target->forceFill(['status' => $status])->save();
        }

        $this->historyCreateAction->execute(
            target: $target,
            action: OperationHistory::ACTION_STATUS_UPDATED,
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
            actorKind: OperationHistory::ACTOR_KIND_SYSTEM,
        );
    }
}
