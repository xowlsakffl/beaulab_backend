<?php

namespace App\Domains\Common\ContentReport\Dto\Staff;

use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Common\ContentReport\Models\ContentReport;
use App\Domains\Common\ContentReport\Models\ContentReportItem;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Common\Media\Models\Media;
use Illuminate\Support\Collection;

final readonly class ContentReportStateForStaffDto
{
    /**
     * @param  array<int, array<string, mixed>>  $reasonCounts
     */
    public function __construct(
        public int $id,
        public string $status,
        public string $label,
        public int $reportCount,
        public int $recentHourReportCount,
        public int $normalVisibleCount,
        public bool $isAutoActionLocked,
        public ?string $firstReportedAt,
        public ?string $lastReportedAt,
        public ?string $autoBlockedAt,
        public ?string $adminHiddenAt,
        public ?string $normalVisibleAt,
        public ?array $processedBy,
        public ?string $processReason,
        public string $warningStatus,
        public string $warningLabel,
        public bool $warning,
        public bool $warningIgnored,
        public ?string $warningProcessedAt,
        public ?array $warningProcessedBy,
        public ?array $latestReport,
        public array $reasonCounts,
    ) {}

    /**
     * @param  Collection<int, object>|array<int, object|array<string, mixed>>  $reasonCounts
     */
    public static function fromModel(
        ContentReportState $state,
        ?ContentReport $latestReport = null,
        Collection|array $reasonCounts = [],
        bool $includeReporterDetail = false,
    ): self {
        return new self(
            id: (int) $state->id,
            status: (string) $state->report_status,
            label: $state->statusLabel(),
            reportCount: (int) $state->report_count,
            recentHourReportCount: (int) $state->recent_hour_report_count,
            normalVisibleCount: (int) $state->normal_visible_count,
            isAutoActionLocked: $state->isAutoActionLocked(),
            firstReportedAt: $state->first_reported_at?->toISOString(),
            lastReportedAt: $state->last_reported_at?->toISOString(),
            autoBlockedAt: $state->auto_blocked_at?->toISOString(),
            adminHiddenAt: $state->admin_hidden_at?->toISOString(),
            normalVisibleAt: $state->normal_visible_at?->toISOString(),
            processedBy: self::processedBy($state),
            processReason: $state->process_reason,
            warningStatus: (string) $state->warning_status,
            warningLabel: $state->warningStatusLabel(),
            warning: (string) $state->warning_status === ContentReportState::WARNING_STATUS_WARNED,
            warningIgnored: (string) $state->warning_status === ContentReportState::WARNING_STATUS_IGNORED,
            warningProcessedAt: $state->warning_processed_at?->toISOString(),
            warningProcessedBy: self::warningProcessedBy($state),
            latestReport: self::latestReport($latestReport, $includeReporterDetail),
            reasonCounts: self::reasonCounts($reasonCounts),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'label' => $this->label,
            'report_count' => $this->reportCount,
            'recent_hour_report_count' => $this->recentHourReportCount,
            'normal_visible_count' => $this->normalVisibleCount,
            'is_auto_action_locked' => $this->isAutoActionLocked,
            'first_reported_at' => $this->firstReportedAt,
            'last_reported_at' => $this->lastReportedAt,
            'auto_blocked_at' => $this->autoBlockedAt,
            'admin_hidden_at' => $this->adminHiddenAt,
            'normal_visible_at' => $this->normalVisibleAt,
            'processed_by' => $this->processedBy,
            'process_reason' => $this->processReason,
            'warning_status' => $this->warningStatus,
            'warning_label' => $this->warningLabel,
            'warning' => $this->warning,
            'warning_ignored' => $this->warningIgnored,
            'warning_processed_at' => $this->warningProcessedAt,
            'warning_processed_by' => $this->warningProcessedBy,
            'latest_report' => $this->latestReport,
            'reason_counts' => $this->reasonCounts,
        ];
    }

    private static function processedBy(ContentReportState $state): ?array
    {
        if (! $state->relationLoaded('processedBy') || ! $state->processedBy) {
            return null;
        }

        $attributes = $state->processedBy->getAttributes();

        return [
            'id' => (int) $state->processedBy->getKey(),
            'name' => (string) ($attributes['name'] ?? ''),
            'email' => isset($attributes['email']) && trim((string) $attributes['email']) !== ''
                ? (string) $attributes['email']
                : null,
        ];
    }

    private static function warningProcessedBy(ContentReportState $state): ?array
    {
        if (! $state->relationLoaded('warningProcessedBy') || ! $state->warningProcessedBy) {
            return null;
        }

        $attributes = $state->warningProcessedBy->getAttributes();

        return [
            'id' => (int) $state->warningProcessedBy->getKey(),
            'name' => (string) ($attributes['name'] ?? ''),
            'email' => isset($attributes['email']) && trim((string) $attributes['email']) !== ''
                ? (string) $attributes['email']
                : null,
        ];
    }

    private static function latestReport(?ContentReport $report, bool $includeReporterDetail): ?array
    {
        if (! $report instanceof ContentReport) {
            return null;
        }

        return [
            'id' => (int) $report->id,
            'reason' => (string) $report->reason,
            'reason_label' => $report->reasonLabel(),
            'reason_text' => $report->reason_text,
            'reporter_ip' => $report->reporter_ip,
            'items' => self::reportItems($report),
            'reporter' => self::reporter($report, $includeReporterDetail),
            'created_at' => $report->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function reportItems(ContentReport $report): array
    {
        if (! $report->relationLoaded('items')) {
            return [];
        }

        return $report->items
            ->map(static fn (ContentReportItem $item): array => [
                'id' => (int) $item->id,
                'target_type' => (string) $item->target_type,
                'target_id' => (int) $item->target_id,
                'target_author_id' => $item->target_author_id !== null ? (int) $item->target_author_id : null,
                'content_snapshot' => $item->content_snapshot,
                'target' => self::reportItemTarget($item),
                'created_at' => $item->created_at?->toISOString(),
            ])
            ->values()
            ->all();
    }

    private static function reportItemTarget(ContentReportItem $item): ?array
    {
        if (! $item->relationLoaded('target') || ! $item->target) {
            return null;
        }

        $target = $item->target;

        if ($target instanceof ChatMessage) {
            return [
                'id' => (int) $target->id,
                'chat_id' => (int) $target->chat_id,
                'created_at' => $target->created_at?->toISOString(),
                'sender' => self::chatMessageSender($target),
                'body' => $target->body,
                'body_preview' => self::contentPreview($target->body),
                'message_type' => (string) $target->message_type,
                'attachments' => self::attachments($target),
            ];
        }

        return [
            'id' => (int) $target->getKey(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function attachments(ChatMessage $message): array
    {
        if (! $message->relationLoaded('attachments')) {
            return [];
        }

        return $message->attachments
            ->map(static fn (Media $media): array => [
                'id' => (int) $media->id,
                'collection' => (string) $media->collection,
                'disk' => (string) $media->disk,
                'path' => $media->publicPath(),
                'url' => $media->publicUrl(),
                'mime_type' => $media->mime_type,
                'size' => $media->size !== null ? (int) $media->size : null,
                'width' => $media->width !== null ? (int) $media->width : null,
                'height' => $media->height !== null ? (int) $media->height : null,
                'sort_order' => (int) $media->sort_order,
                'metadata' => $media->publicMetadata(),
                'created_at' => $media->created_at?->toISOString(),
                'updated_at' => $media->updated_at?->toISOString(),
            ])
            ->values()
            ->all();
    }

    private static function chatMessageSender(ChatMessage $message): ?array
    {
        if (! $message->relationLoaded('sender') || ! $message->sender) {
            return null;
        }

        $attributes = $message->sender->getAttributes();

        return [
            'id' => (int) $message->sender->getKey(),
            'name' => (string) ($attributes['name'] ?? ''),
            'nickname' => isset($attributes['nickname']) && trim((string) $attributes['nickname']) !== ''
                ? (string) $attributes['nickname']
                : null,
            'email' => isset($attributes['email']) && trim((string) $attributes['email']) !== ''
                ? (string) $attributes['email']
                : null,
        ];
    }

    private static function reporter(ContentReport $report, bool $includeDetail): ?array
    {
        if (! $report->relationLoaded('reporter') || ! $report->reporter) {
            return null;
        }

        $attributes = $report->reporter->getAttributes();

        $payload = [
            'id' => (int) $report->reporter->getKey(),
            'name' => (string) ($attributes['name'] ?? ''),
            'nickname' => isset($attributes['nickname']) && trim((string) $attributes['nickname']) !== ''
                ? (string) $attributes['nickname']
                : null,
            'email' => isset($attributes['email']) && trim((string) $attributes['email']) !== ''
                ? (string) $attributes['email']
                : null,
        ];

        if (! $includeDetail) {
            return $payload;
        }

        return [
            ...$payload,
            'phone' => isset($attributes['phone']) && trim((string) $attributes['phone']) !== ''
                ? (string) $attributes['phone']
                : null,
            'warning_count' => (int) ($attributes['warning_count'] ?? 0),
            'created_at' => $report->reporter->created_at?->toISOString(),
        ];
    }

    private static function contentPreview(mixed $value): ?string
    {
        $content = trim((string) $value);

        if ($content === '') {
            return null;
        }

        return mb_strlen($content) > 120 ? mb_substr($content, 0, 120).'...' : $content;
    }

    /**
     * @param  Collection<int, object>|array<int, object|array<string, mixed>>  $reasonCounts
     * @return array<int, array<string, mixed>>
     */
    private static function reasonCounts(Collection|array $reasonCounts): array
    {
        return collect($reasonCounts)
            ->map(static function (object|array $row): array {
                $reason = is_array($row) ? (string) ($row['reason'] ?? '') : (string) ($row->reason ?? '');
                $count = is_array($row) ? (int) ($row['count'] ?? 0) : (int) ($row->count ?? 0);

                return [
                    'reason' => $reason,
                    'label' => ContentReport::reasonLabels()[$reason] ?? $reason,
                    'count' => $count,
                ];
            })
            ->values()
            ->all();
    }
}
