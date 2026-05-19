<?php

namespace App\Domains\Common\ContentReport\Queries\User;

use App\Domains\Common\ContentReport\Models\ContentReport;
use App\Domains\Common\ContentReport\Models\ContentReportItem;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class ContentReportCreateForUserQuery
{
    public function createReport(array $attributes): ContentReport
    {
        return ContentReport::query()->create($attributes);
    }

    /**
     * @param  array<int, array{target_type: string, target_id: int}>  $items
     */
    public function hasExistingReportItem(int $reporterUserId, array $items): bool
    {
        if ($items === []) {
            return false;
        }

        return ContentReportItem::query()
            ->where('reporter_user_id', $reporterUserId)
            ->where(function (Builder $query) use ($items): void {
                foreach ($items as $item) {
                    $query->orWhere(function (Builder $itemQuery) use ($item): void {
                        $itemQuery
                            ->where('target_type', $item['target_type'])
                            ->where('target_id', $item['target_id']);
                    });
                }
            })
            ->exists();
    }

    /**
     * @param  array<int, array{target_type: string, target_id: int, target_author_id: ?int, content_snapshot: ?string}>  $items
     */
    public function createReportItems(ContentReport $report, int $reporterUserId, array $items): void
    {
        if ($items === []) {
            return;
        }

        $now = now();

        ContentReportItem::query()->insert(array_map(
            static fn (array $item): array => [
                'content_report_id' => (int) $report->id,
                'reporter_user_id' => $reporterUserId,
                'target_type' => $item['target_type'],
                'target_id' => $item['target_id'],
                'target_author_id' => $item['target_author_id'],
                'content_snapshot' => $item['content_snapshot'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $items,
        ));
    }

    public function getStateForUpdate(string $targetType, int $targetId): ?ContentReportState
    {
        return ContentReportState::query()
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->lockForUpdate()
            ->first();
    }

    public function createState(string $targetType, int $targetId): ContentReportState
    {
        return ContentReportState::query()->create([
            'target_type' => $targetType,
            'target_id' => $targetId,
        ]);
    }

    public function countReports(string $targetType, int $targetId): int
    {
        return ContentReport::query()
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->count();
    }

    public function countReportsSince(string $targetType, int $targetId, CarbonInterface $since): int
    {
        return ContentReport::query()
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->where('created_at', '>=', $since)
            ->count();
    }
}
