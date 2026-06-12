<?php

declare(strict_types=1);

namespace App\Domains\Common\ContentReport\Dto\Staff;

final readonly class ReportedContentSummaryForStaffDto
{
    public function __construct(
        private int $reportedOrAutoBlockedCount,
        private int $todayReportCount,
        private int $recent30DaysAdminHiddenCount,
        private int $recent30DaysNormalVisibleCount,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            reportedOrAutoBlockedCount: (int) ($data['reported_or_auto_blocked_count'] ?? 0),
            todayReportCount: (int) ($data['today_report_count'] ?? 0),
            recent30DaysAdminHiddenCount: (int) ($data['recent_30_days_admin_hidden_count'] ?? 0),
            recent30DaysNormalVisibleCount: (int) ($data['recent_30_days_normal_visible_count'] ?? 0),
        );
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'reported_or_auto_blocked_count' => $this->reportedOrAutoBlockedCount,
            'today_report_count' => $this->todayReportCount,
            'recent_30_days_admin_hidden_count' => $this->recent30DaysAdminHiddenCount,
            'recent_30_days_normal_visible_count' => $this->recent30DaysNormalVisibleCount,
        ];
    }
}
