<?php

declare(strict_types=1);

namespace App\Domains\HospitalVideo\Queries\Staff;

use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Database\Eloquent\Builder;

final class HospitalVideoSummaryForStaffQuery
{
    /**
     * @return array<string, int>
     */
    public function get(): array
    {
        $baseQuery = HospitalVideo::query();

        return [
            'normal_videos' => (clone $baseQuery)
                ->where(fn (Builder $query) => $this->applyNormalFilter($query))
                ->count(),
            'limited_videos' => (clone $baseQuery)
                ->where(fn (Builder $query) => $this->applyLimitedFilter($query))
                ->count(),
            'reported_videos' => (clone $baseQuery)
                ->whereHas('contentReportState', fn (Builder $query) => $query
                    ->whereIn('report_status', [
                        ContentReportState::STATUS_REPORTED,
                        ContentReportState::STATUS_AUTO_BLOCKED,
                    ]))
                ->count(),
        ];
    }

    public function applySummaryFilter(Builder $builder, string $summaryFilter): void
    {
        if ($summaryFilter === HospitalVideo::SUMMARY_FILTER_NORMAL) {
            $builder->where(fn (Builder $query) => $this->applyNormalFilter($query));

            return;
        }

        if ($summaryFilter === HospitalVideo::SUMMARY_FILTER_LIMITED) {
            $builder->where(fn (Builder $query) => $this->applyLimitedFilter($query));

            return;
        }

        if ($summaryFilter === HospitalVideo::SUMMARY_FILTER_REPORTED) {
            $builder->whereHas('contentReportState', fn (Builder $query) => $query
                ->whereIn('report_status', [
                    ContentReportState::STATUS_REPORTED,
                    ContentReportState::STATUS_AUTO_BLOCKED,
                ]));
        }
    }

    private function applyNormalFilter(Builder $builder): void
    {
        $builder
            ->where('hospital_status', HospitalVideo::HOSPITAL_STATUS_PUBLIC)
            ->where('admin_status', HospitalVideo::ADMIN_STATUS_NORMAL)
            ->where(function (Builder $query): void {
                $query
                    ->whereDoesntHave('contentReportState')
                    ->orWhereHas('contentReportState', fn (Builder $stateQuery) => $stateQuery
                        ->whereNotIn('report_status', [
                            ContentReportState::STATUS_REPORTED,
                            ContentReportState::STATUS_AUTO_BLOCKED,
                            ContentReportState::STATUS_ADMIN_HIDDEN,
                        ]));
            });
    }

    private function applyLimitedFilter(Builder $builder): void
    {
        $builder->where(function (Builder $query): void {
            $query
                ->where('hospital_status', HospitalVideo::HOSPITAL_STATUS_PRIVATE)
                ->orWhereHas('contentReportState', fn (Builder $stateQuery) => $stateQuery
                    ->where('report_status', ContentReportState::STATUS_ADMIN_HIDDEN));
        });
    }
}
