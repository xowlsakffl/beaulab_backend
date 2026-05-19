<?php

namespace App\Domains\Common\ContentReport\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Common\Support\PaginatedResponse;
use App\Domains\Common\ContentReport\Models\ContentReport;
use App\Domains\Common\ContentReport\Models\ContentReportItem;
use App\Domains\Common\ContentReport\Queries\Staff\ReportedContentDetailForStaffQuery;
use App\Domains\Common\ContentReport\Support\ContentReportTargetRegistry;
use Illuminate\Support\Facades\Gate;

final class ReportedContentReportsForStaffAction
{
    private const REPORTS_PER_PAGE = 10;

    public function __construct(
        private readonly ReportedContentDetailForStaffQuery $query,
    ) {}

    public function execute(string $targetAlias, int $targetId, int $page = 1): array
    {
        $targetClass = ContentReportTargetRegistry::classForAlias($targetAlias);

        if ($targetClass === null) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '지원하지 않는 신고 대상입니다.');
        }

        Gate::authorize('viewAny', $targetClass);
        $this->query->state($targetClass, $targetId);

        $reports = $this->query->reportsQuery($targetClass, $targetId)->paginate(
            perPage: self::REPORTS_PER_PAGE,
            pageName: 'page',
            page: $page,
        );

        return PaginatedResponse::fromPaginator(
            $reports,
            fn (ContentReport $report): array => $this->report($report),
        );
    }

    private function report(ContentReport $report): array
    {
        return [
            'id' => (int) $report->id,
            'reason' => (string) $report->reason,
            'reason_label' => $report->reasonLabel(),
            'reason_text' => $report->reason_text,
            'reporter_ip' => $report->reporter_ip,
            'items' => $this->items($report),
            'reporter' => $this->reporter($report),
            'created_at' => $report->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function items(ContentReport $report): array
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
                'created_at' => $item->created_at?->toISOString(),
            ])
            ->values()
            ->all();
    }

    private function reporter(ContentReport $report): ?array
    {
        if (! $report->relationLoaded('reporter') || ! $report->reporter) {
            return null;
        }

        $attributes = $report->reporter->getAttributes();

        return [
            'id' => (int) $report->reporter->getKey(),
            'name' => (string) ($attributes['name'] ?? ''),
            'nickname' => isset($attributes['nickname']) && trim((string) $attributes['nickname']) !== ''
                ? (string) $attributes['nickname']
                : null,
            'email' => isset($attributes['email']) && trim((string) $attributes['email']) !== ''
                ? (string) $attributes['email']
                : null,
        ];
    }
}
