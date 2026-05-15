<?php

namespace App\Domains\Common\ContentReport\Queries\Staff;

use App\Domains\Common\ContentReport\Models\ContentReportState;

final class ContentReportStateStatusUpdateForStaffQuery
{
    public function getStateForUpdate(string $targetType, int $targetId): ?ContentReportState
    {
        return ContentReportState::query()
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->lockForUpdate()
            ->first();
    }
}
