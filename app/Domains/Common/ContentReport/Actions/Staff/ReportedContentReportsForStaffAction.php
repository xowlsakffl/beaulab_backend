<?php

namespace App\Domains\Common\ContentReport\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Common\Support\PaginatedResponse;
use App\Domains\Common\ContentReport\Models\ContentReport;
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

        $reports = PaginatedResponse::paginateWithFallback(
            queryFactory: fn () => $this->query->reportsQuery($targetClass, $targetId),
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
            'reporter' => $this->reporter($report),
            'created_at' => $report->created_at?->toISOString(),
        ];
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
