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

    public function execute(Model $reporter, Model $target, array $payload): void
    {
        ContentReportTargetRegistry::assertSupported($target);

        $reporterUserId = (int) $reporter->getKey();
        $targetType = $target::class;
        $targetId = (int) $target->getKey();
        $reportItems = $this->reportItems($target, $payload);

        DB::transaction(function () use ($reporterUserId, $targetType, $targetId, $target, $payload, $reportItems): void {
            // 테스트 중복 신고 확인을 위해 1인 1신고 정책을 임시 비활성화한다.
            // 운영 기준 복구 시 아래 검사를 다시 활성화하고 content_report_items unique 제약도 함께 복구해야 한다.
            // if ($this->query->hasExistingReportItem($reporterUserId, $reportItems)) {
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
                    'reporter_ip' => $this->normalizeIp($payload['reporter_ip'] ?? null),
                ]);

                $this->query->createReportItems($report, $reporterUserId, $reportItems);
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

            if ($state->isAutoActionLocked()) {
                if ($previousReportStatus === ContentReportState::STATUS_NORMAL_VISIBLE) {
                    $state->report_status = ContentReportState::STATUS_REEXPOSED;
                }
            } elseif ($previousReportStatus !== ContentReportState::STATUS_ADMIN_HIDDEN) {
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
        });

        ContentReportSummaryCache::forgetForTarget($target);
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

    /**
     * @return array<int, array{target_type: string, target_id: int, target_author_id: ?int, content_snapshot: ?string}>
     */
    private function reportItems(Model $target, array $payload): array
    {
        $items = $payload['items'] ?? null;

        if (is_array($items) && $items !== []) {
            return collect($items)
                ->map(fn (array $item): array => [
                    'target_type' => (string) $item['target_type'],
                    'target_id' => (int) $item['target_id'],
                    'target_author_id' => isset($item['target_author_id']) ? (int) $item['target_author_id'] : null,
                    'content_snapshot' => isset($item['content_snapshot'])
                        ? $this->normalizeSnapshot($item['content_snapshot'])
                        : null,
                ])
                ->values()
                ->all();
        }

        return [[
            'target_type' => $target::class,
            'target_id' => (int) $target->getKey(),
            'target_author_id' => $this->targetAuthorId($target),
            'content_snapshot' => $this->contentSnapshot($target, $payload),
        ]];
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
            return $this->normalizeSnapshot($payload['content_snapshot']);
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

    private function normalizeSnapshot(mixed $value): ?string
    {
        $snapshot = trim((string) $value);

        return $snapshot === '' ? null : mb_substr($snapshot, 0, 2000);
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
