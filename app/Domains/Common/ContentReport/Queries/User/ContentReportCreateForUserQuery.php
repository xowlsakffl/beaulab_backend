<?php

namespace App\Domains\Common\ContentReport\Queries\User;

use App\Domains\Common\ContentReport\Models\ContentReport;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use Carbon\CarbonInterface;

final class ContentReportCreateForUserQuery
{
    public function findExistingReport(int $reporterUserId, string $targetType, int $targetId): ?ContentReport
    {
        return ContentReport::query()
            ->where('reporter_user_id', $reporterUserId)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->first();
    }

    public function createReport(array $attributes): ContentReport
    {
        return ContentReport::query()->create($attributes);
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
