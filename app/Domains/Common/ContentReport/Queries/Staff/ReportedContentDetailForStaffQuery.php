<?php

namespace App\Domains\Common\ContentReport\Queries\Staff;

use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Common\ContentReport\Models\ContentReport;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class ReportedContentDetailForStaffQuery
{
    /**
     * @param  class-string<Model>  $targetClass
     */
    public function state(string $targetClass, int $targetId): ContentReportState
    {
        return ContentReportState::query()
            ->where('target_type', $targetClass)
            ->where('target_id', $targetId)
            ->where('report_status', '!=', ContentReportState::STATUS_NONE)
            ->with(['processedBy:id,name,email', 'warningProcessedBy:id,name,email'])
            ->firstOrFail();
    }

    /**
     * @param  class-string<Model>  $targetClass
     * @return Builder<ContentReport>
     */
    public function reportsQuery(string $targetClass, int $targetId): Builder
    {
        return ContentReport::query()
            ->where('target_type', $targetClass)
            ->where('target_id', $targetId)
            ->with(['reporter:id,name,nickname,email', 'items'])
            ->latest('id');
    }

    /**
     * @param  class-string<Model>  $targetClass
     */
    public function latestReport(string $targetClass, int $targetId): ?ContentReport
    {
        return ContentReport::query()
            ->where('target_type', $targetClass)
            ->where('target_id', $targetId)
            ->with([
                'reporter:'.$this->reporterColumns($targetClass),
                'items.target',
            ])
            ->latest('id')
            ->first();
    }

    /**
     * @param  class-string<Model>  $targetClass
     * @return Collection<int, object>
     */
    public function reasonCounts(string $targetClass, int $targetId): Collection
    {
        return ContentReport::query()
            ->where('target_type', $targetClass)
            ->where('target_id', $targetId)
            ->select('reason')
            ->selectRaw('COUNT(*) AS count')
            ->groupBy('reason')
            ->orderByDesc('count')
            ->get();
    }

    /**
     * @param  class-string<Model>  $targetClass
     */
    private function reporterColumns(string $targetClass): string
    {
        return $targetClass === ChatMessage::class
            ? 'id,name,nickname,email,phone,warning_count,created_at'
            : 'id,name,nickname,email';
    }
}
